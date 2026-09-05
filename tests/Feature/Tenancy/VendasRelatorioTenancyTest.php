<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendasRelatorioTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_vendedor_nao_acessa_relatorio_analitico_por_url_direta(): void
    {
        $empresa = Empresa::factory()->create();
        $vendedor = $this->usuario($empresa, UserRole::VENDEDOR);

        $this->actingAs($vendedor);

        foreach ([
            route('sale.analyticalSales'),
            route('sale.getSalesAnalytical'),
            route('sale.dados'),
            route('sale.listarVendas'),
            route('sale.exportar'),
        ] as $rota) {
            $this->getJson($rota)->assertForbidden();
        }
    }

    public function test_master_ve_somente_empresa_ativa_e_angariacao_sem_corte_de_ano(): void
    {
        $empresaA = Empresa::factory()->create(['nome_fantasia' => 'Corretora A']);
        $empresaB = Empresa::factory()->create(['nome_fantasia' => 'Corretora B']);
        $catalogo = app(TabulationCatalog::class);
        $catalogo->provision($empresaA->id);
        $catalogo->provision($empresaB->id);
        $vendaA = $catalogo->id($empresaA->id, TabulationCode::VENDA);
        $vendaB = $catalogo->id($empresaB->id, TabulationCode::VENDA);
        $master = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $vendedorA = $this->usuario($empresaA, UserRole::VENDEDOR, 'Vendedor A');
        $vendedorB = $this->usuario($empresaB, UserRole::VENDEDOR, 'Vendedor B');
        $contatoA = $this->contato($empresaA, $vendedorA);
        $contatoB = $this->contato($empresaB, $vendedorB);
        $dataHistorica = now()->setDate(2025, 10, 15);

        $this->venda($empresaA, $vendedorA, $contatoA, $vendaA, 900, 90, $dataHistorica);
        $vendaDaEmpresaAtiva = $this->venda($empresaB, $vendedorB, $contatoB, $vendaB, 100, 50, $dataHistorica);

        $resposta = $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->getJson(route('sale.dados', ['ano' => 2025]))
            ->assertOk()
            ->assertJsonPath('data.resumo_geral.total_contratos', 1)
            ->assertJsonPath('data.resumo_geral.valor_total', 150)
            ->assertJsonPath('data.vendas_totais.0.id', $vendaDaEmpresaAtiva)
            ->assertJsonPath('data.vendas_por_vendedor.0.vendedor', 'Vendedor B');

        $this->assertCount(1, $resposta->json('data.vendas_totais'));

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->getJson(route('sale.dados', ['vendedor_id' => $vendedorA->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vendedor_id');

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->getJson(route('sale.listarVendas', ['status' => $vendaA]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_vendedor_baixa_somente_boleto_da_propria_venda(): void
    {
        Storage::fake();
        $empresa = Empresa::factory()->create();
        $catalogo = app(TabulationCatalog::class);
        $catalogo->provision($empresa->id);
        $status = $catalogo->id($empresa->id, TabulationCode::VENDA);
        $vendedor = $this->usuario($empresa, UserRole::VENDEDOR, 'Titular');
        $colega = $this->usuario($empresa, UserRole::VENDEDOR, 'Colega');
        $contatoTitular = $this->contato($empresa, $vendedor);
        $contatoColega = $this->contato($empresa, $colega);
        Storage::put('boletos/proprio.pdf', 'boleto próprio');
        Storage::put('boletos/colega.pdf', 'boleto colega');
        $vendaPropria = $this->venda($empresa, $vendedor, $contatoTitular, $status, 100, 0, now(), 'boletos/proprio.pdf');
        $vendaColega = $this->venda($empresa, $colega, $contatoColega, $status, 100, 0, now(), 'boletos/colega.pdf');

        $this->actingAs($vendedor)
            ->get(route('sale.downloadBoleto', $vendaColega))
            ->assertForbidden();

        $this->get(route('sale.downloadBoleto', $vendaPropria))
            ->assertOk()
            ->assertDownload();
    }

    public function test_vendedor_recebe_codigo_semantico_com_rotulo_personalizado_e_historico_so_da_propria_venda(): void
    {
        $empresa = Empresa::factory()->create();
        $catalogo = app(TabulationCatalog::class);
        $catalogo->provision($empresa->id);
        $implantadoId = $catalogo->id($empresa->id, TabulationCode::IMPLANTADO);
        DB::table('tabulacoes')->where('id', $implantadoId)->update(['descricao' => 'Cliente ativado']);

        $vendedor = $this->usuario($empresa, UserRole::VENDEDOR, 'Vendedor titular');
        $colega = $this->usuario($empresa, UserRole::VENDEDOR, 'Outro vendedor');
        $vendaPropria = $this->venda(
            $empresa,
            $vendedor,
            $this->contato($empresa, $vendedor),
            $implantadoId,
            100,
            0,
            now()
        );
        $vendaDoColega = $this->venda(
            $empresa,
            $colega,
            $this->contato($empresa, $colega),
            $implantadoId,
            200,
            0,
            now()
        );
        DB::table('vendas_historico')->insert([
            [
                'empresa_id' => $empresa->id,
                'venda_id' => $vendaPropria,
                'user_id' => $vendedor->id,
                'tabulacao_nova_id' => $implantadoId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $empresa->id,
                'venda_id' => $vendaDoColega,
                'user_id' => $colega->id,
                'tabulacao_nova_id' => $implantadoId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($vendedor)
            ->getJson(route('sale.getResultsBroker'))
            ->assertOk()
            ->assertJsonCount(1, 'vendas')
            ->assertJsonPath('vendas.0.id', $vendaPropria)
            ->assertJsonPath('vendas.0.codigo', TabulationCode::IMPLANTADO)
            ->assertJsonPath('vendas.0.descricao', 'Cliente ativado');

        $this->getJson(route('sale.details', $vendaPropria))
            ->assertOk()
            ->assertJsonPath('codigo', TabulationCode::IMPLANTADO)
            ->assertJsonPath('descricao', 'Cliente ativado');

        $this->getJson(route('sale.history', $vendaPropria))
            ->assertOk()
            ->assertJsonCount(1, 'historico')
            ->assertJsonPath('historico.0.status_novo', 'Cliente ativado')
            ->assertJsonPath('historico.0.status_novo_codigo', TabulationCode::IMPLANTADO);

        $this->getJson(route('sale.details', $vendaDoColega))->assertNotFound();
        $this->getJson(route('sale.history', $vendaDoColega))->assertNotFound();
    }

    private function usuario(Empresa $empresa, int $role, ?string $nome = null): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => $role,
            'ativo' => 'Y',
            'name' => $nome ?? fake()->name(),
        ]);
    }

    private function contato(Empresa $empresa, User $vendedor): int
    {
        return DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $vendedor->id,
            'nome_cliente' => 'Cliente '.$vendedor->name,
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function venda(
        Empresa $empresa,
        User $vendedor,
        int $contatoId,
        int $tabulacaoId,
        float $contrato,
        float $angariacao,
        mixed $data,
        ?string $boleto = null
    ): int {
        return DB::table('vendas')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_id' => $vendedor->id,
            'contato_id' => $contatoId,
            'tabulacao_id' => $tabulacaoId,
            'nome_contrato' => 'Contrato '.$vendedor->name,
            'valor_contrato' => $contrato,
            'angariacao_status' => $angariacao > 0 ? 'SIM' : 'NAO',
            'angariacao_valor' => $angariacao,
            'vidas' => 1,
            'data_vigencia' => now()->toDateString(),
            'path_boleto_disponivel' => $boleto,
            'created_at' => $data,
            'updated_at' => $data,
        ]);
    }
}
