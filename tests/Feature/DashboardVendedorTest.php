<?php

namespace Tests\Feature;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardVendedorTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedor;

    private User $concorrente;

    private int $contatoId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            'id' => UserRole::VENDEDOR,
            'tipo_usuario' => 'VENDEDOR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->vendedor = $this->user($this->empresa, 'Ana Vendas');
        $this->concorrente = $this->user($this->empresa, 'Bruno Vendas');

        foreach ([
            Tabulations::VENDA => 'VENDA',
            Tabulations::IMPLANTADO => 'IMPLANTADO',
            Tabulations::ESTORNO => 'ESTORNO',
            Tabulations::DECLINIO => 'DECLINIO',
        ] as $id => $description) {
            DB::table('tabulacoes')->insert([
                'id' => $id,
                'empresa_id' => $this->empresa->id,
                'descricao' => $description,
                'tipo_tabulacao' => 'A',
                'efetivo' => $id === Tabulations::IMPLANTADO ? 'Y' : 'N',
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Contato do dashboard',
            'cpf' => '12345678900',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_dashboard_anual_usa_regra_de_valor_valido_e_detalha_os_destaques(): void
    {
        $this->sale($this->vendedor, Tabulations::IMPLANTADO, 1000, 100, 'Plano Ouro', 'Operadora A', now()->setMonth(2));
        $this->sale($this->vendedor, Tabulations::ESTORNO, 300, 30, 'Plano Ouro', 'Operadora A', now()->setMonth(4));
        $this->sale($this->vendedor, Tabulations::DECLINIO, 9000, 900, 'Plano Recusado', 'Operadora B', now()->setMonth(5));
        $this->sale($this->vendedor, Tabulations::VENDA, 700, 0, 'Plano Antigo', 'Operadora A', now()->subYear());

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'year']))
            ->assertOk();

        $response->assertJsonPath('annual.valid_value', 1430)
            ->assertJsonPath('annual.contract_value', 1300)
            ->assertJsonPath('annual.fundraising_value', 130)
            ->assertJsonPath('annual.sales_count', 2)
            ->assertJsonPath('annual.implanted_count', 1)
            ->assertJsonPath('annual.reversed_count', 1)
            ->assertJsonPath('top_product.name', 'Plano Ouro')
            ->assertJsonPath('top_product.operator', 'Operadora A')
            ->assertJsonPath('top_product.count', 2)
            ->assertJsonPath('largest_sale.total', 1100)
            ->assertJsonCount(12, 'monthly')
            ->assertJsonCount(2, 'detail_sales');
    }

    public function test_ranking_anual_respeita_empresa_exclusao_e_desempate_oficial(): void
    {
        $this->sale($this->vendedor, Tabulations::IMPLANTADO, 1000, 100);
        $this->sale($this->vendedor, Tabulations::ESTORNO, 300, 30);
        $this->sale($this->concorrente, Tabulations::VENDA, 1500);

        $excluded = $this->user($this->empresa, 'Fora do ranking', true);
        $this->sale($excluded, Tabulations::IMPLANTADO, 50000);

        $otherCompany = Empresa::factory()->create();
        $otherSeller = $this->user($otherCompany, 'Outra empresa');
        $this->sale($otherSeller, Tabulations::IMPLANTADO, 70000);

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'year']))
            ->assertOk();

        $response->assertJsonPath('ranking.position', 2)
            ->assertJsonPath('ranking.total_sellers', 2)
            ->assertJsonPath('ranking.distance_to_previous', 70)
            ->assertJsonCount(2, 'ranking.leaders')
            ->assertJsonPath('ranking.leaders.0.seller', 'Bruno Vendas')
            ->assertJsonPath('ranking.leaders.1.seller', 'Ana Vendas');
    }

    public function test_ranking_exibe_top_tres_e_vendedor_logado_sem_divulgar_valores(): void
    {
        $this->sale($this->vendedor, Tabulations::VENDA, 1000);

        foreach ([
            'Primeiro Lugar' => 5000,
            'Segundo Lugar' => 4000,
            'Terceiro Lugar' => 3000,
            'Quarto Lugar' => 2000,
        ] as $name => $value) {
            $this->sale($this->user($this->empresa, $name), Tabulations::VENDA, $value);
        }

        $ranking = $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'year']))
            ->assertOk()
            ->json('ranking');

        $this->assertSame(5, $ranking['position']);
        $this->assertCount(4, $ranking['leaders']);
        $this->assertSame(['Primeiro Lugar', 'Segundo Lugar', 'Terceiro Lugar', 'Ana Vendas'], array_column($ranking['leaders'], 'seller'));
        $this->assertSame([1, 2, 3, 5], array_column($ranking['leaders'], 'position'));
        $this->assertTrue($ranking['leaders'][3]['is_current_user']);

        foreach ($ranking['leaders'] as $leader) {
            $this->assertArrayNotHasKey('valid_value', $leader);
        }

        foreach (['year', 'month', 'quarter'] as $period) {
            $periodRanking = $this->actingAs($this->vendedor)
                ->getJson(route('dashboard.vendedor.metrics', ['period' => $period]))
                ->assertOk()
                ->json('ranking');

            foreach ($periodRanking['leaders'] as $leader) {
                $this->assertArrayNotHasKey('valid_value', $leader);
            }
        }
    }

    public function test_filtros_de_ano_mes_e_trimestre_afetam_o_relatorio_completo(): void
    {
        $now = now();
        $quarterMonth = $now->month === $now->copy()->startOfQuarter()->month
            ? $now->copy()->addMonth()
            : $now->copy()->startOfQuarter();
        $yearMonth = $now->quarter === 1 ? $now->copy()->setMonth(12) : $now->copy()->setMonth(1);

        $this->sale($this->vendedor, Tabulations::VENDA, 700, 70, createdAt: $now);
        $this->sale($this->vendedor, Tabulations::VENDA, 500, 50, createdAt: $quarterMonth);
        $this->sale($this->vendedor, Tabulations::VENDA, 300, 30, createdAt: $yearMonth);

        $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'year']))
            ->assertOk()
            ->assertJsonPath('period', 'year')
            ->assertJsonPath('annual.valid_value', 1650)
            ->assertJsonPath('detail_total', 3)
            ->assertJsonCount(12, 'monthly');

        $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'quarter']))
            ->assertOk()
            ->assertJsonPath('period', 'quarter')
            ->assertJsonPath('annual.valid_value', 1320)
            ->assertJsonPath('detail_total', 2)
            ->assertJsonCount(3, 'monthly');

        $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'month']))
            ->assertOk()
            ->assertJsonPath('period', 'month')
            ->assertJsonPath('annual.valid_value', 770)
            ->assertJsonPath('detail_total', 1)
            ->assertJsonCount(1, 'monthly')
            ->assertJsonCount(1, 'detail_sales');
    }

    public function test_produto_lider_agrupa_o_plano_mesmo_quando_ha_mais_de_uma_operadora(): void
    {
        $this->sale($this->vendedor, Tabulations::VENDA, 500, product: 'Plano Flex', operator: 'Operadora A');
        $this->sale($this->vendedor, Tabulations::VENDA, 400, product: 'Plano Flex', operator: 'Operadora B');
        $this->sale($this->vendedor, Tabulations::VENDA, 800, product: 'Plano Premium', operator: 'Operadora A');

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'year']))
            ->assertOk();

        $response->assertJsonPath('top_product.name', 'Plano Flex')
            ->assertJsonPath('top_product.count', 2)
            ->assertJsonPath('top_product.total', 900)
            ->assertJsonPath('top_product.operator', 'Operadora A + 1 operadora');
    }

    public function test_rejeita_periodo_invalido(): void
    {
        $this->actingAs($this->vendedor)
            ->getJson(route('dashboard.vendedor.metrics', ['period' => 'semester']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);
    }

    private function user(Empresa $empresa, string $name, bool $excluded = false): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'name' => $name,
            'ativo' => 'Y',
            'excluir_ranking' => $excluded,
        ]);
    }

    private function sale(
        User $seller,
        int $status,
        float $contract,
        float $fundraising = 0,
        string $product = 'Plano Essencial',
        string $operator = 'Operadora A',
        $createdAt = null
    ): void {
        DB::table('vendas')->insert([
            'empresa_id' => $seller->empresa_id,
            'user_id' => $seller->id,
            'contato_id' => $this->contatoId,
            'tabulacao_id' => $status,
            'nome_contrato' => 'Cliente '.$seller->name,
            'nome_plano' => $product,
            'operadora' => $operator,
            'valor_contrato' => $contract,
            'angariacao_status' => $fundraising > 0 ? 'SIM' : 'NAO',
            'angariacao_valor' => $fundraising,
            'data_vigencia' => now()->toDateString(),
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);
    }
}
