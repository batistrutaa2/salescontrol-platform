<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialReportSettingsTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            UserRole::ADMINISTRATIVO => 'ADMINISTRATIVO',
            UserRole::DEVELOPER => 'DEVELOPER',
            UserRole::FINANCEIRO => 'FINANCEIRO',
        ] as $id => $role) {
            DB::table('user_roles')->updateOrInsert(
                ['id' => $id],
                ['tipo_usuario' => $role, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_master_configura_somente_o_relatorio_da_empresa_ativa(): void
    {
        [$empresaA, $empresaB, $master] = $this->cenarioMaster();

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->patch(route('financeiro.relatorio.configuracao'), [
                'financeiro_mrr_janela_meses' => 2,
                'financeiro_historico_meses' => 18,
                'financeiro_previsao_meses' => 9,
            ])
            ->assertRedirect(route('financeiro.relatorio'));

        $empresaA->refresh();
        $empresaB->refresh();

        $this->assertSame(3, $empresaA->financeiro_mrr_janela_meses);
        $this->assertSame(12, $empresaA->financeiro_historico_meses);
        $this->assertSame(6, $empresaA->financeiro_previsao_meses);
        $this->assertSame(2, $empresaB->financeiro_mrr_janela_meses);
        $this->assertSame(18, $empresaB->financeiro_historico_meses);
        $this->assertSame(9, $empresaB->financeiro_previsao_meses);
        $this->assertNull($master->fresh()->empresa_id);
    }

    public function test_perfil_financeiro_visualiza_parametros_mas_nao_pode_altera_los(): void
    {
        $empresa = Empresa::factory()->create([
            'financeiro_mrr_janela_meses' => 4,
            'financeiro_historico_meses' => 10,
            'financeiro_previsao_meses' => 5,
        ]);
        $financeiro = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::FINANCEIRO,
            'ativo' => 'Y',
        ]);

        $this->actingAs($financeiro)
            ->get(route('financeiro.relatorio'))
            ->assertOk()
            ->assertSee('MRR (média de 4 meses)')
            ->assertSee('Realizado (10 meses) + Previsão (5 meses)')
            ->assertDontSee('Salvar parâmetros');

        $this->patch(route('financeiro.relatorio.configuracao'), [
            'financeiro_mrr_janela_meses' => 1,
            'financeiro_historico_meses' => 1,
            'financeiro_previsao_meses' => 1,
        ])->assertForbidden();

        $this->assertSame(4, $empresa->fresh()->financeiro_mrr_janela_meses);
    }

    public function test_calculos_usam_janelas_da_empresa_ativa_e_nao_dados_de_outro_tenant(): void
    {
        Carbon::setTestNow('2026-09-15 10:00:00');
        [$empresaA, $empresaB, $master] = $this->cenarioMaster();
        $empresaB->update([
            'financeiro_mrr_janela_meses' => 2,
            'financeiro_historico_meses' => 2,
            'financeiro_previsao_meses' => 1,
        ]);

        $vendaB = $this->criarVenda($empresaB);
        $this->criarRecebivel($empresaB, $vendaB, 1, 100, 'PAGO', '2026-09-10', true);
        $this->criarRecebivel($empresaB, $vendaB, 2, 100, 'PAGO', '2026-08-10', true);
        $this->criarRecebivel($empresaB, $vendaB, 3, 900, 'PAGO', '2026-07-10', true);
        $this->criarRecebivel($empresaB, $vendaB, 4, 50, 'PENDENTE', null, false, '2026-09-25');
        $this->criarRecebivel($empresaB, $vendaB, 5, 80, 'PENDENTE', null, false, '2026-10-10');

        $vendaA = $this->criarVenda($empresaA);
        $this->criarRecebivel($empresaA, $vendaA, 1, 700, 'PAGO', '2026-09-10', true);

        $response = $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->postJson(route('financeiro.relatorio.fetch'))
            ->assertOk()
            ->assertJsonPath('kpis.mrr', 100)
            ->assertJsonCount(2, 'previsaoReceita.historico')
            ->assertJsonCount(1, 'previsaoReceita.previsao');

        $this->assertSame(50.0, (float) $response->json('previsaoReceita.previsao.0.valor'));
    }

    public function test_parametros_financeiros_invalidos_sao_rejeitados(): void
    {
        [, $empresa, $master] = $this->cenarioMaster();

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresa->id])
            ->patch(route('financeiro.relatorio.configuracao'), [
                'financeiro_mrr_janela_meses' => 0,
                'financeiro_historico_meses' => 61,
                'financeiro_previsao_meses' => 37,
            ])
            ->assertSessionHasErrors([
                'financeiro_mrr_janela_meses',
                'financeiro_historico_meses',
                'financeiro_previsao_meses',
            ]);
    }

    /** @return array{Empresa, Empresa, User} */
    private function cenarioMaster(): array
    {
        $empresaA = Empresa::factory()->create();
        $empresaB = Empresa::factory()->create();
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        return [$empresaA, $empresaB, $master];
    }

    private function criarVenda(Empresa $empresa): array
    {
        $usuario = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $usuario->id,
            'nome_cliente' => 'Cliente '.$empresa->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendaId = DB::table('vendas')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_id' => $usuario->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato '.$empresa->id,
            'data_vigencia' => today(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $vendaId, 'usuario_id' => $usuario->id];
    }

    private function criarRecebivel(
        Empresa $empresa,
        array $venda,
        int $parcela,
        float $valor,
        string $status,
        ?string $dataRecebimento,
        bool $vitalicio,
        ?string $dataPrevista = null,
    ): void {
        DB::table('recebiveis')->insert([
            'empresa_id' => $empresa->id,
            'venda_id' => $venda['id'],
            'vendedor_id' => $venda['usuario_id'],
            'operadora' => 'Operadora '.$empresa->id,
            'plano' => 'Plano',
            'parcela' => $parcela,
            'vitalicio' => $vitalicio,
            'valor' => $valor,
            'data_prevista' => $dataPrevista ?? $dataRecebimento,
            'data_recebimento' => $dataRecebimento,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
