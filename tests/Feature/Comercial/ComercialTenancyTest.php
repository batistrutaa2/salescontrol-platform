<?php

namespace Tests\Feature\Comercial;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\Empresa;
use App\Models\PreditivaConfiguracao;
use App\Models\Tabulacoes;
use App\Models\User;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComercialTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Empresa $outraEmpresa;

    private User $usuario;

    private User $outroUsuario;

    private Contatos $contato;

    private Contatos $outroContato;

    private Tabulacoes $tabulacao;

    private Tabulacoes $outraTabulacao;

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
                'id' => UserRole::DEVELOPER,
                'tipo_usuario' => 'DEVELOPER',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->outraEmpresa = Empresa::factory()->create();
        $this->usuario = $this->usuario($this->empresa);
        $this->outroUsuario = $this->usuario($this->outraEmpresa);
        $this->contato = $this->contato($this->empresa, $this->usuario, 'Lead interno');
        $this->outroContato = $this->contato($this->outraEmpresa, $this->outroUsuario, 'Lead externo');
        $this->tabulacao = $this->tabulacao($this->empresa, 'PROSPECCAO-A');
        $this->outraTabulacao = $this->tabulacao($this->outraEmpresa, 'PROSPECCAO-B');
    }

    public function test_nao_envia_contato_de_outra_empresa_para_preditiva(): void
    {
        $this->actingAs($this->usuario)
            ->postJson(route('comercial.sendLeadPredictive'), ['id' => $this->outroContato->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id');

        $this->assertDatabaseMissing('preditiva', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->outroContato->id,
        ]);
    }

    public function test_remarketing_exibe_rotulo_configurado_pela_empresa(): void
    {
        $catalogo = app(TabulationCatalog::class);
        $catalogo->provision($this->empresa->id);
        $remarketingId = $catalogo->id($this->empresa->id, TabulationCode::REMARKETING);
        DB::table('tabulacoes')->where('id', $remarketingId)->update(['descricao' => 'Recuperação VIP']);
        DB::table('contatos_corretores')->insert([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->contato->id,
            'user_id' => $this->usuario->id,
            'tabulacao_id' => $remarketingId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->usuario)
            ->getJson(route('comercial.getRemarketingLeads'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.motivo_remarketing', 'Recuperação VIP');
    }

    public function test_kanban_recebe_codigo_semantico_e_limites_da_empresa_ativa(): void
    {
        PreditivaConfiguracao::query()->create([
            'empresa_id' => $this->empresa->id,
            'kanban_inatividade_alerta_dias' => 3,
            'kanban_inatividade_urgente_dias' => 6,
            'kanban_inatividade_critica_dias' => 9,
        ]);
        PreditivaConfiguracao::query()->create([
            'empresa_id' => $this->outraEmpresa->id,
            'kanban_inatividade_alerta_dias' => 30,
            'kanban_inatividade_urgente_dias' => 60,
            'kanban_inatividade_critica_dias' => 90,
        ]);
        DB::table('contatos_corretores')->insert([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->contato->id,
            'user_id' => $this->usuario->id,
            'tabulacao_id' => $this->tabulacao->id,
            'temperatura' => 'FRIO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->usuario)
            ->get(route('comercial.kanban'))
            ->assertOk()
            ->assertSee('data-inatividade-alerta-dias="3"', false)
            ->assertSee('data-inatividade-urgente-dias="6"', false)
            ->assertSee('data-inatividade-critica-dias="9"', false)
            ->assertDontSee('data-inatividade-alerta-dias="30"', false);

        $response = $this->getJson(route('comercial.getClientComercial'))->assertOk();
        $item = collect($response->json())->flatMap(fn (array $board) => $board['item'])->firstWhere('id', $this->contato->id);

        $this->assertSame('PROSPECCAO-A', $item['tabulacao-codigo']);
    }

    public function test_nao_transfere_nem_remove_da_preditiva_lead_de_outra_empresa(): void
    {
        DB::table('preditiva')->insert([
            'empresa_id' => $this->outraEmpresa->id,
            'contato_id' => $this->outroContato->id,
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->usuario)
            ->postJson(route('comercial.transferContact'), [
                'idMailing' => $this->outroContato->id,
                'user_id' => $this->usuario->id,
                'tabulation_id' => $this->tabulacao->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idMailing');

        $this->assertDatabaseHas('preditiva', [
            'empresa_id' => $this->outraEmpresa->id,
            'contato_id' => $this->outroContato->id,
        ]);
        $this->assertDatabaseMissing('transferencia_contatos', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->outroContato->id,
        ]);
    }

    public function test_nao_cria_comentario_ou_atividade_em_contato_de_outra_empresa(): void
    {
        $this->actingAs($this->usuario)
            ->postJson(route('comercial.saveComment'), [
                'id_mailing' => $this->outroContato->id,
                'id_tabulacao' => $this->outraTabulacao->id,
                'anotacao' => 'Não deve atravessar tenants',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_mailing', 'id_tabulacao']);

        $this->assertDatabaseMissing('comentarios', ['anotacao' => 'Não deve atravessar tenants']);
        $this->assertDatabaseMissing('lead_atividades', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->outroContato->id,
        ]);
    }

    public function test_nao_descarta_nem_converte_contato_de_outra_empresa(): void
    {
        $this->actingAs($this->usuario)
            ->postJson(route('comercial.descartarClientePreditiva'), [
                'contato_id' => $this->outroContato->id,
                'tabulacao' => 'SEM INTERESSE',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contato_id');

        $this->postJson(route('comercial.converterClientePreditiva'), [
            'contato_id' => $this->outroContato->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('contato_id');

        $this->assertDatabaseMissing('log_preditiva', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->outroContato->id,
        ]);
        $this->assertDatabaseHas('contatos', [
            'id' => $this->outroContato->id,
            'empresa_id' => $this->outraEmpresa->id,
            'status' => 'Y',
        ]);
    }

    public function test_acoes_em_lote_validam_limites_e_tenant_antes_de_mutar(): void
    {
        $this->actingAs($this->usuario);

        $this->postJson(route('comercial.sendMultipleLeadsPredictive'), [
            'ids' => [$this->contato->id, $this->outroContato->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids.1');

        $this->postJson(route('comercial.discardMultipleLeads'), [
            'ids' => [$this->outroContato->id],
            'clearPreditiva' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('ids.0');

        $this->postJson(route('comercial.discardMultipleLeads'), [
            'ids' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');

        $this->assertDatabaseHas('contatos', [
            'id' => $this->contato->id,
            'empresa_id' => $this->empresa->id,
            'status' => 'Y',
        ]);
        $this->assertDatabaseHas('contatos', [
            'id' => $this->outroContato->id,
            'empresa_id' => $this->outraEmpresa->id,
            'status' => 'Y',
        ]);
        $this->assertDatabaseMissing('preditiva', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->contato->id,
        ]);
    }

    public function test_preditiva_limita_rotulos_recebidos_do_cliente(): void
    {
        $this->actingAs($this->usuario)
            ->postJson(route('comercial.descartarClientePreditiva'), [
                'contato_id' => $this->contato->id,
                'tabulacao' => str_repeat('A', 121),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tabulacao');

        $this->assertDatabaseMissing('log_preditiva', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->contato->id,
        ]);
    }

    public function test_master_sem_empresa_de_origem_permanece_visivel_como_autor_no_tenant_ativo(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
            'name' => 'Master da plataforma',
        ]);
        DB::table('contatos_corretores')->insert([
            'empresa_id' => $this->outraEmpresa->id,
            'contato_id' => $this->outroContato->id,
            'user_id' => $this->outroUsuario->id,
            'tabulacao_id' => $this->outraTabulacao->id,
            'temperatura' => 'FRIO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('comentarios')->insert([
            'empresa_id' => $this->outraEmpresa->id,
            'user_id' => $master->id,
            'contato_id' => $this->outroContato->id,
            'anotacao' => 'Ajuste administrativo do master',
            'visivel' => 'Y',
            'supervisao' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->outraEmpresa->id])
            ->get(route('comercial.openClient', $this->outroContato->id))
            ->assertOk()
            ->assertSee('Ajuste administrativo do master')
            ->assertSee('Master da plataforma');

        $this->assertDatabaseHas('users', [
            'id' => $master->id,
            'empresa_id' => null,
        ]);
    }

    public function test_demanda_rejeita_responsavel_de_outra_empresa(): void
    {
        $this->actingAs($this->usuario)
            ->postJson(route('demandas.store'), [
                'titulo' => 'Demanda segura',
                'prioridade' => 'MEDIA',
                'assigned_to' => $this->outroUsuario->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assigned_to');

        $this->assertDatabaseMissing('demandas', ['titulo' => 'Demanda segura']);
    }

    public function test_demanda_nao_atribui_master_global_como_responsavel_operacional(): void
    {
        $master = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($this->usuario)
            ->postJson(route('demandas.store'), [
                'titulo' => 'Demanda sem atribuição global',
                'prioridade' => 'MEDIA',
                'assigned_to' => $master->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assigned_to');

        $this->assertDatabaseMissing('demandas', ['assigned_to' => $master->id]);
    }

    public function test_listagem_de_leads_nao_inclui_contato_orfao_de_outra_empresa(): void
    {
        $response = $this->actingAs($this->usuario)
            ->getJson(route('mailing.getLeads'))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($this->contato->id));
        $this->assertFalse($ids->contains($this->outroContato->id));
    }

    public function test_busca_de_usuario_por_nome_permanece_na_empresa_ativa(): void
    {
        $nome = 'Corretor Homônimo';
        $externo = User::factory()->create([
            'empresa_id' => $this->outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'name' => $nome,
        ]);
        $interno = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'name' => $nome,
        ]);

        $this->actingAs($this->usuario);
        $encontrado = app(TenantContext::class)->run(
            $this->empresa->id,
            fn () => app(UsuariosRepositoryInterface::class)->getUserSearchName($nome),
        );

        $this->assertNotSame($externo->id, $encontrado?->id);
        $this->assertSame($interno->id, $encontrado?->id);
        $this->assertSame($this->empresa->id, $encontrado?->empresa_id);
    }

    public function test_agendamentos_listam_e_alteram_somente_contatos_da_empresa_ativa(): void
    {
        DB::table('agendamentos')->insert([
            [
                'empresa_id' => $this->empresa->id,
                'user_id' => $this->usuario->id,
                'contato_id' => $this->contato->id,
                'horario_agendamento' => now()->addDay(),
                'observacao' => 'Agenda interna',
                'notificado' => 'N',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $this->outraEmpresa->id,
                'user_id' => $this->outroUsuario->id,
                'contato_id' => $this->outroContato->id,
                'horario_agendamento' => now()->addDay(),
                'observacao' => 'Agenda externa',
                'notificado' => 'N',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($this->usuario)
            ->getJson(route('comercial.getSchedules'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome_cliente', 'Lead interno')
            ->assertJsonMissing(['observacao' => 'Agenda externa']);

        $this->post(route('comercial.sendSchedule'), [
            'contato_id' => $this->outroContato->id,
            'horario_agendamento' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'observacao' => 'Alteração indevida',
        ])->assertNotFound();

        $this->assertDatabaseHas('agendamentos', [
            'empresa_id' => $this->outraEmpresa->id,
            'contato_id' => $this->outroContato->id,
            'observacao' => 'Agenda externa',
        ]);
    }

    public function test_cadastro_manual_aceita_origem_generica_e_persiste_data_correta(): void
    {
        $this->tabulacao($this->empresa, 'PROSPECCAO');

        $this->actingAs($this->usuario)
            ->post(route('comercial.createLead'), [
                'nome_base' => 'Evento regional setembro',
                'nome_cliente' => 'Cliente Manual',
                'cpf' => '111.444.777-35',
                'data_nascimento' => '1990-05-12',
                'telefone1' => '(11) 91234-5678',
                'email' => 'cliente@example.test',
                'valor_plano_atual' => 'R$ 150,00',
            ])
            ->assertRedirect(route('comercial.kanban'));

        $this->assertDatabaseHas('contatos', [
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->usuario->id,
            'nome_base' => 'Evento regional setembro',
            'nome_cliente' => 'Cliente Manual',
            'data_nascimento' => '1990-05-12',
            'cpf' => '11144477735',
        ]);
    }

    public function test_master_cadastra_lead_com_autoria_global_sem_se_tornar_proprietario(): void
    {
        app(TabulationCatalog::class)->provision($this->outraEmpresa->id);
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->outraEmpresa->id])
            ->post(route('comercial.createLead'), [
                'nome_base' => 'Cadastro técnico',
                'nome_cliente' => 'Lead sem proprietário',
                'cpf' => '529.982.247-25',
                'telefone1' => '(11) 99876-5432',
            ])
            ->assertRedirect(route('comercial.kanban'));

        $contatoId = DB::table('contatos')
            ->where('empresa_id', $this->outraEmpresa->id)
            ->where('nome_cliente', 'Lead sem proprietário')
            ->value('id');

        $this->assertNotNull($contatoId);
        $this->assertDatabaseHas('contatos', [
            'id' => $contatoId,
            'empresa_id' => $this->outraEmpresa->id,
            'user_import_id' => $master->id,
        ]);
        $this->assertDatabaseHas('contatos_corretores', [
            'empresa_id' => $this->outraEmpresa->id,
            'contato_id' => $contatoId,
            'user_id' => null,
        ]);
    }

    public function test_cadastro_manual_rejeita_documento_invalido(): void
    {
        $this->actingAs($this->usuario)
            ->from(route('comercial.createClient'))
            ->post(route('comercial.createLead'), [
                'nome_cliente' => 'Cliente Inválido',
                'cpf' => '111.111.111-11',
                'telefone1' => '(11) 91234-5678',
            ])
            ->assertRedirect(route('comercial.createClient'))
            ->assertSessionHasErrors('cpf');

        $this->assertDatabaseMissing('contatos', [
            'empresa_id' => $this->empresa->id,
            'nome_cliente' => 'Cliente Inválido',
        ]);
    }

    private function usuario(Empresa $empresa): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
    }

    private function contato(Empresa $empresa, User $usuario, string $nome): Contatos
    {
        return Contatos::create([
            'empresa_id' => $empresa->id,
            'user_import_id' => $usuario->id,
            'nome_cliente' => $nome,
            'status' => 'Y',
        ]);
    }

    private function tabulacao(Empresa $empresa, string $codigo): Tabulacoes
    {
        return Tabulacoes::create([
            'empresa_id' => $empresa->id,
            'codigo' => $codigo,
            'descricao' => $codigo,
            'tipo_tabulacao' => 'C',
            'efetivo' => 'N',
            'status' => 'Y',
        ]);
    }
}
