<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\Empresa;
use App\Models\PreditivaConfiguracao;
use App\Models\User;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Eloquent\ContatosRepository;
use App\Services\TabulationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReciclagemLeadsTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresaA;

    private Empresa $empresaB;

    private User $adminA;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            [
                'id' => UserRole::ADMINISTRATIVO,
                'tipo_usuario' => 'ADMINISTRATIVO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => UserRole::VENDEDOR,
                'tipo_usuario' => 'VENDEDOR',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $this->empresaA = Empresa::factory()->create(['nome_fantasia' => 'Empresa A']);
        $this->empresaB = Empresa::factory()->create(['nome_fantasia' => 'Empresa B']);
        $this->adminA = $this->usuario($this->empresaA, 'Admin A');
        $this->adminB = $this->usuario($this->empresaB, 'Admin B');
        app(TabulationCatalog::class)->provision($this->empresaA->id);
        app(TabulationCatalog::class)->provision($this->empresaB->id);
    }

    public function test_envio_manual_recicla_somente_leads_elegiveis_da_empresa_ativa(): void
    {
        $leadA = $this->leadFrio($this->empresaA, $this->adminA, 'Lead A');
        $leadB = $this->leadFrio($this->empresaB, $this->adminB, 'Lead B');

        $this->actingAs($this->adminA)
            ->postJson(route('comercial.reciclagem.enviar'), [
                'ids' => [$leadA->id, $leadB->id],
                'todos' => false,
            ])
            ->assertOk()
            ->assertJsonPath('resultado.enviados', 1)
            ->assertJsonPath('resultado.ignorados', 1)
            ->assertJsonPath('resultado.erros', 0);

        $this->assertDatabaseHas('preditiva', [
            'empresa_id' => $this->empresaA->id,
            'contato_id' => $leadA->id,
            'status' => 'Y',
        ]);
        $this->assertDatabaseMissing('preditiva', ['contato_id' => $leadB->id]);
        $this->assertDatabaseHas('preditiva_envios', [
            'empresa_id' => $this->empresaA->id,
            'contato_id' => $leadA->id,
            'enviado_por' => $this->adminA->id,
            'origem' => 'MANUAL',
        ]);
    }

    public function test_ordenacao_paginacao_e_lote_malformados_sao_rejeitados_antes_da_query(): void
    {
        $this->actingAs($this->adminA)
            ->getJson(route('comercial.reciclagem.elegiveis', [
                'order' => [['column' => 5, 'dir' => 'desc; DROP TABLE contatos']],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order.0.dir');

        $this->getJson(route('comercial.reciclagem.historico', ['length' => 100000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('length');

        $this->postJson(route('comercial.reciclagem.enviar'), ['todos' => false, 'ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');
    }

    public function test_configuracao_ignora_empresa_do_payload_e_altera_apenas_tenant_ativo(): void
    {
        PreditivaConfiguracao::query()->create([
            'empresa_id' => $this->empresaB->id,
            'dias_sem_contato_reenvio' => 180,
            'envio_automatico_ativo' => false,
            'limite_envio_diario' => 25,
            'mascote_dias_sem_atividade' => 12,
            'mascote_limite_sugestoes' => 4,
            'lock_expiracao_horas' => 6,
            'indicadores_janela_dias' => 60,
            'kanban_inatividade_alerta_dias' => 10,
            'kanban_inatividade_urgente_dias' => 20,
            'kanban_inatividade_critica_dias' => 30,
        ]);

        $this->actingAs($this->adminA)
            ->postJson(route('comercial.reciclagem.config.save'), [
                'empresa_id' => $this->empresaB->id,
                'dias_sem_contato_reenvio' => 45,
                'envio_automatico_ativo' => true,
                'limite_envio_diario' => 80,
                'mascote_dias_sem_atividade' => 7,
                'mascote_limite_sugestoes' => 15,
                'lock_expiracao_horas' => 3,
                'indicadores_janela_dias' => 15,
                'kanban_inatividade_alerta_dias' => 4,
                'kanban_inatividade_urgente_dias' => 8,
                'kanban_inatividade_critica_dias' => 12,
            ])
            ->assertOk();

        $this->assertDatabaseHas('preditiva_configuracoes', [
            'empresa_id' => $this->empresaA->id,
            'dias_sem_contato_reenvio' => 45,
            'envio_automatico_ativo' => 1,
            'limite_envio_diario' => 80,
            'mascote_dias_sem_atividade' => 7,
            'mascote_limite_sugestoes' => 15,
            'lock_expiracao_horas' => 3,
            'indicadores_janela_dias' => 15,
            'kanban_inatividade_alerta_dias' => 4,
            'kanban_inatividade_urgente_dias' => 8,
            'kanban_inatividade_critica_dias' => 12,
        ]);
        $this->assertDatabaseHas('preditiva_configuracoes', [
            'empresa_id' => $this->empresaB->id,
            'dias_sem_contato_reenvio' => 180,
            'envio_automatico_ativo' => 0,
            'limite_envio_diario' => 25,
            'mascote_dias_sem_atividade' => 12,
            'mascote_limite_sugestoes' => 4,
            'lock_expiracao_horas' => 6,
            'indicadores_janela_dias' => 60,
            'kanban_inatividade_alerta_dias' => 10,
            'kanban_inatividade_urgente_dias' => 20,
            'kanban_inatividade_critica_dias' => 30,
        ]);
    }

    public function test_configuracao_rejeita_limites_de_inatividade_fora_de_ordem(): void
    {
        $this->actingAs($this->adminA)
            ->postJson(route('comercial.reciclagem.config.save'), [
                'dias_sem_contato_reenvio' => 45,
                'envio_automatico_ativo' => true,
                'limite_envio_diario' => 80,
                'mascote_dias_sem_atividade' => 7,
                'mascote_limite_sugestoes' => 15,
                'lock_expiracao_horas' => 3,
                'indicadores_janela_dias' => 15,
                'kanban_inatividade_alerta_dias' => 20,
                'kanban_inatividade_urgente_dias' => 10,
                'kanban_inatividade_critica_dias' => 5,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'kanban_inatividade_urgente_dias',
                'kanban_inatividade_critica_dias',
            ]);

        $this->assertDatabaseMissing('preditiva_configuracoes', [
            'empresa_id' => $this->empresaA->id,
        ]);
    }

    public function test_indicador_de_envios_respeita_janela_e_empresa_ativa(): void
    {
        PreditivaConfiguracao::query()->create([
            'empresa_id' => $this->empresaA->id,
            'indicadores_janela_dias' => 5,
        ]);
        $leadA = $this->leadFrio($this->empresaA, $this->adminA, 'Lead A');
        $leadB = $this->leadFrio($this->empresaB, $this->adminB, 'Lead B');

        DB::table('preditiva_envios')->insert([
            $this->envio($this->empresaA, $leadA, now()),
            $this->envio($this->empresaA, $leadA, now()->subDays(6)),
            $this->envio($this->empresaB, $leadB, now()),
        ]);

        $this->actingAs($this->adminA)
            ->getJson(route('comercial.reciclagem.resumo'))
            ->assertOk()
            ->assertJsonPath('resumo.enviados_na_janela', 1)
            ->assertJsonPath('resumo.indicadores_janela_dias', 5);
    }

    public function test_mascote_usa_regra_da_empresa_e_ignora_dias_informados_na_url(): void
    {
        $vendedor = User::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        PreditivaConfiguracao::query()->create([
            'empresa_id' => $this->empresaA->id,
            'mascote_dias_sem_atividade' => 7,
            'mascote_limite_sugestoes' => 15,
        ]);

        $repository = \Mockery::mock(ContatosRepository::class);
        $repository->shouldReceive('getSugestoesContato')
            ->once()
            ->with($vendedor->id, $this->empresaA->id, 7, 15)
            ->andReturn(collect());
        $this->app->instance(ContatosRepositoryInterface::class, $repository);

        $this->actingAs($vendedor)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('comercial.sugestaoContato', ['dias' => 1]))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    private function envio(Empresa $empresa, Contatos $contato, \DateTimeInterface $enviadoEm): array
    {
        return [
            'empresa_id' => $empresa->id,
            'contato_id' => $contato->id,
            'enviado_em' => $enviadoEm,
            'origem' => 'MANUAL',
            'enviado_por' => null,
            'created_at' => $enviadoEm,
            'updated_at' => $enviadoEm,
        ];
    }

    public function test_expiracao_do_lock_respeita_regra_e_empresa_ativa(): void
    {
        PreditivaConfiguracao::query()->create([
            'empresa_id' => $this->empresaA->id,
            'lock_expiracao_horas' => 3,
        ]);
        $leadAntigo = $this->leadFrio($this->empresaA, $this->adminA, 'Lock antigo');
        $leadRecente = $this->leadFrio($this->empresaA, $this->adminA, 'Lock recente');
        $leadExterno = $this->leadFrio($this->empresaB, $this->adminB, 'Lock externo');
        DB::table('contatos')
            ->whereIn('id', [$leadAntigo->id, $leadRecente->id, $leadExterno->id])
            ->update(['cpf' => '']);

        DB::table('preditiva')->insert([
            [
                'empresa_id' => $this->empresaA->id,
                'contato_id' => $leadAntigo->id,
                'user_id' => $this->adminA->id,
                'data_atribuicao' => now()->subHours(4),
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $this->empresaA->id,
                'contato_id' => $leadRecente->id,
                'user_id' => $this->adminA->id,
                'data_atribuicao' => now()->subHours(2),
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $this->empresaB->id,
                'contato_id' => $leadExterno->id,
                'user_id' => $this->adminA->id,
                'data_atribuicao' => now()->subHours(4),
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($this->adminA)
            ->postJson(route('comercial.getClientesPreditiva'))
            ->assertOk()
            ->assertExactJson([]);

        $this->assertDatabaseHas('preditiva', [
            'empresa_id' => $this->empresaA->id,
            'contato_id' => $leadAntigo->id,
            'user_id' => null,
            'data_atribuicao' => null,
        ]);
        $this->assertDatabaseHas('preditiva', [
            'empresa_id' => $this->empresaA->id,
            'contato_id' => $leadRecente->id,
            'user_id' => $this->adminA->id,
        ]);
        $this->assertDatabaseHas('preditiva', [
            'empresa_id' => $this->empresaB->id,
            'contato_id' => $leadExterno->id,
            'user_id' => $this->adminA->id,
        ]);
    }

    public function test_historico_nao_expoe_autor_comum_de_outra_empresa(): void
    {
        $leadA = $this->leadFrio($this->empresaA, $this->adminA, 'Lead auditado');
        DB::table('preditiva_envios')->insert([
            'empresa_id' => $this->empresaA->id,
            'contato_id' => $leadA->id,
            'enviado_em' => now(),
            'origem' => 'MANUAL',
            'enviado_por' => $this->adminB->id,
            'situacao_origem' => 'SEM_ATRIBUICAO',
            'dias_inativo' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->adminA)
            ->getJson(route('comercial.reciclagem.historico'))
            ->assertOk()
            ->assertJsonPath('data.0.enviado_por', 'Autor indisponível')
            ->assertDontSee($this->adminB->name);
    }

    private function usuario(Empresa $empresa, string $nome): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'name' => $nome,
            'ativo' => 'Y',
        ]);
    }

    private function leadFrio(Empresa $empresa, User $autor, string $nome): Contatos
    {
        return Contatos::query()->create([
            'empresa_id' => $empresa->id,
            'user_import_id' => $autor->id,
            'nome_cliente' => $nome,
            'cpf' => $empresa->id.'1144477735',
            'telefone1' => '11999990000',
            'status' => 'Y',
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
        ]);
    }
}
