<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\Empresa;
use App\Models\PreditivaRegraPriorizacao;
use App\Models\User;
use App\Repositories\Eloquent\PreditivaRegraRepository;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MailingAccessTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_vendedor_nao_acessa_administracao_de_leads_por_url_direta(): void
    {
        $empresa = Empresa::factory()->create();
        $vendedor = $this->usuario($empresa, UserRole::VENDEDOR);
        $this->actingAs($vendedor);

        foreach ([
            route('mailing.viewLeads'),
            route('mailing.getAllLeadsServerSide'),
            route('mailing.getLeadKPIs'),
            route('mailing.preditiva'),
            route('mailing.reservatorio.index'),
        ] as $rota) {
            $this->getJson($rota)->assertForbidden();
        }

        $this->postJson(route('mailing.reactivateLead'))->assertForbidden();
        $this->postJson(route('mailing.bulkDeleteLeads'))->assertForbidden();
        $this->deleteJson(route('mailing.deleteMailing', 1))->assertForbidden();
    }

    public function test_filtros_e_mutacoes_rejeitam_ids_de_outra_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $admin = $this->usuario($empresa, UserRole::ADMINISTRATIVO);
        $vendedorExterno = $this->usuario($outraEmpresa, UserRole::VENDEDOR);
        $leadLocal = $this->contato($empresa, $admin, 'Lead local', 'Y');
        $leadExterno = $this->contato($outraEmpresa, $vendedorExterno, 'Lead externo', 'N');

        $this->actingAs($admin)
            ->getJson(route('mailing.getLeadKPIs', ['corretor' => $vendedorExterno->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('corretor');

        $this->getJson(route('mailing.getAllLeadsServerSide', [
            'order' => [['column' => 5, 'dir' => 'desc, sleep(1)']],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order.0.dir');

        $this->postJson(route('mailing.reactivateLead'), ['id' => $leadExterno->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id');

        $this->postJson(route('mailing.bulkDiscardLeads'), [
            'ids' => [$leadLocal->id, $leadExterno->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.1');

        $this->postJson(route('mailing.sendDiscardedToPreditiva'), [
            'id' => $leadExterno->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('id');

        $this->postJson(route('mailing.sendMultipleDiscardedToPreditiva'), [
            'ids' => [$leadExterno->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids.0');

        DB::table('log_preditiva')->insert([
            'empresa_id' => $outraEmpresa->id,
            'user_id' => $vendedorExterno->id,
            'contato_id' => $leadExterno->id,
            'tabulacao' => 'LOG EXTERNO',
            'acao' => 'DESCARTE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->postJson(route('preditiva.limparLogs'), [
            'ids' => [$leadExterno->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('ids.0');

        $this->deleteJson(route('mailing.deleteMailing', $leadExterno->id))->assertNotFound();

        $this->assertDatabaseHas('contatos', [
            'id' => $leadLocal->id,
            'empresa_id' => $empresa->id,
            'status' => 'Y',
        ]);
        $this->assertDatabaseHas('contatos', [
            'id' => $leadExterno->id,
            'empresa_id' => $outraEmpresa->id,
            'status' => 'N',
        ]);
        $this->assertDatabaseHas('log_preditiva', [
            'empresa_id' => $outraEmpresa->id,
            'contato_id' => $leadExterno->id,
            'tabulacao' => 'LOG EXTERNO',
        ]);
    }

    public function test_master_consulta_somente_leads_da_empresa_ativa(): void
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($empresaA->id);
        app(TabulationCatalog::class)->provision($empresaB->id);
        $master = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $this->contato($empresaA, $master, 'Lead A', 'Y');
        $leadB = $this->contato($empresaB, $master, 'Lead B', 'Y');

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->getJson(route('mailing.getAllLeadsServerSide'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $leadB->id);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->getJson(route('mailing.getLeadKPIs'))
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_relacao_adulterada_de_outro_tenant_nao_oculta_lead_local(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $admin = $this->usuario($empresa, UserRole::ADMINISTRATIVO);
        $usuarioExterno = $this->usuario($outraEmpresa, UserRole::VENDEDOR);
        $leadLocal = $this->contato($empresa, $admin, 'Lead local preservado', 'Y');
        app(TabulationCatalog::class)->provision($outraEmpresa->id);
        $tabulacaoExterna = DB::table('tabulacoes')
            ->where('empresa_id', $outraEmpresa->id)
            ->value('id');

        DB::table('contatos_corretores')->insert([
            'empresa_id' => $outraEmpresa->id,
            'contato_id' => $leadLocal->id,
            'user_id' => $usuarioExterno->id,
            'tabulacao_id' => $tabulacaoExterna,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('mailing.getLeads'))
            ->assertOk();

        $this->assertContains($leadLocal->id, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_comentarios_nao_expoem_autor_nem_contato_de_outra_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $admin = $this->usuario($empresa, UserRole::ADMINISTRATIVO);
        $usuarioExterno = $this->usuario($outraEmpresa, UserRole::VENDEDOR);
        $usuarioExterno->forceFill(['name' => 'Corretor externo confidencial'])->save();
        $leadLocal = $this->contato($empresa, $admin, 'Lead local', 'Y');
        $leadExterno = $this->contato($outraEmpresa, $usuarioExterno, 'Lead externo', 'Y');

        DB::table('comentarios')->insert([
            'empresa_id' => $empresa->id,
            'user_id' => $usuarioExterno->id,
            'contato_id' => $leadLocal->id,
            'anotacao' => 'Comentário local com autor adulterado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('comercial.getComentariosLead', $leadLocal->id))
            ->assertOk()
            ->assertJsonPath('0.autor', null)
            ->assertDontSee('Corretor externo confidencial');

        $this->getJson(route('comercial.getComentariosLead', $leadExterno->id))
            ->assertNotFound();
    }

    public function test_supervisor_mantem_acesso_gerencial_aos_indicadores(): void
    {
        $empresa = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($empresa->id);
        $supervisor = $this->usuario($empresa, UserRole::SUPERVISOR);

        $this->actingAs($supervisor)
            ->getJson(route('mailing.getLeadKPIs'))
            ->assertOk();
    }

    public function test_falha_interna_da_preditiva_nao_expoe_sql_ao_navegador(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = $this->usuario($empresa, UserRole::ADMINISTRATIVO);
        $repository = Mockery::mock(PreditivaRegraRepository::class);
        $repository->shouldReceive('getRegrasByEmpresa')
            ->once()
            ->with($empresa->id)
            ->andThrow(new RuntimeException('SQLSTATE segredo_tenant tabela_interna'));
        $this->app->instance(\App\Repositories\Contracts\PreditivaRegraRepositoryInterface::class, $repository);

        $this->actingAs($admin)
            ->getJson(route('preditiva.regras.index'))
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Não foi possível carregar as regras neste momento.')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('segredo_tenant');
    }

    public function test_regras_preditivas_nao_podem_ser_alteradas_por_id_de_outra_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $admin = $this->usuario($empresa, UserRole::ADMINISTRATIVO);
        $regraLocal = PreditivaRegraPriorizacao::create($this->regra($empresa, 'Regra local'));
        $regraExterna = PreditivaRegraPriorizacao::create($this->regra($outraEmpresa, 'Regra externa'));
        $payload = [
            'nome' => 'Tentativa de alteração',
            'campo' => 'vidas',
            'operador' => '>=',
            'valor' => '3',
            'peso' => 20,
        ];

        $this->actingAs($admin)
            ->putJson(route('preditiva.regras.update', $regraExterna->id), $payload)
            ->assertNotFound();
        $this->postJson(route('preditiva.regras.toggle', $regraExterna->id))->assertNotFound();
        $this->deleteJson(route('preditiva.regras.destroy', $regraExterna->id))->assertNotFound();
        $this->postJson(route('preditiva.regras.reordenar'), [
            'ordens' => [$regraLocal->id, $regraExterna->id],
        ])->assertForbidden();

        $this->assertDatabaseHas('preditiva_regras_priorizacao', [
            'id' => $regraExterna->id,
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'Regra externa',
            'ativo' => 'Y',
            'ordem' => 1,
        ]);
    }

    private function usuario(Empresa $empresa, int $role): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => $role,
            'ativo' => 'Y',
        ]);
    }

    private function contato(Empresa $empresa, User $importador, string $nome, string $status): Contatos
    {
        return Contatos::create([
            'empresa_id' => $empresa->id,
            'user_import_id' => $importador->id,
            'nome_cliente' => $nome,
            'status' => $status,
        ]);
    }

    private function regra(Empresa $empresa, string $nome): array
    {
        return [
            'empresa_id' => $empresa->id,
            'nome' => $nome,
            'campo' => 'vidas',
            'operador' => '>=',
            'valor' => '2',
            'peso' => 10,
            'ativo' => 'Y',
            'ordem' => 1,
        ];
    }
}
