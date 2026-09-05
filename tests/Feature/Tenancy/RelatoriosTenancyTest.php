<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelatoriosTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_vendedor_nao_acessa_relatorios_gerenciais_por_url_direta(): void
    {
        $empresa = Empresa::factory()->create();
        $vendedor = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $hoje = now()->toDateString();

        $rotasGet = [
            route('relatorios.distribuicaoLeads'),
            route('relatorios.distribuicaoLeads.dados'),
            route('relatorios.distribuicaoLeads.vendedorDetalhes', $vendedor->id),
            route('pabx.getLigacoess'),
            route('pabx.getLigacoes', [$vendedor->id, $hoje, $hoje]),
            route('relatorios.preditiva.predictiveReport'),
            route('relatorios.performanceVendedor'),
            route('relatorios.performanceVendedor.dados'),
            route('relatorios.implantacoes'),
            route('relatorios.implantacoes.dados'),
            route('relatorios.implantacoes.listar'),
            route('relatorios.desempenhoAnual'),
            route('relatorios.desempenhoAnual.dados'),
            route('relatorios.aproveitamento'),
            route('relatorios.aproveitamento.dados'),
        ];

        $this->actingAs($vendedor);

        foreach ($rotasGet as $rota) {
            $this->getJson($rota)->assertForbidden();
        }

        $this->postJson(route('relatorios.preditiva.buscar'))->assertForbidden();
        $this->postJson(route('relatorios.aproveitamento.analise'))->assertForbidden();
    }

    public function test_master_consulta_somente_a_empresa_ativa_e_nao_expoe_usuario_externo(): void
    {
        [$empresaA, $empresaB, $master, $vendedorA, $vendedorB] = $this->cenario();
        $agora = now();
        $contatoA = $this->contato($empresaA->id, $vendedorA->id, 'Cliente A');
        $contatoB = $this->contato($empresaB->id, $vendedorB->id, 'Cliente B');

        DB::table('log_preditiva')->insert([
            [
                'empresa_id' => $empresaA->id,
                'user_id' => $vendedorA->id,
                'contato_id' => $contatoA,
                'tabulacao' => 'COTACAO',
                'acao' => 'DESCARTE',
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
            [
                'empresa_id' => $empresaB->id,
                'user_id' => $vendedorB->id,
                'contato_id' => $contatoB,
                'tabulacao' => 'CONVERTIDO',
                'acao' => 'CONVERSAO',
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
            // Simula dado legado inconsistente: o relatório não pode revelar o usuário A.
            [
                'empresa_id' => $empresaB->id,
                'user_id' => $vendedorA->id,
                'contato_id' => $contatoB,
                'tabulacao' => 'LIGAR_DEPOIS',
                'acao' => 'DESCARTE',
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
        ]);

        $resposta = $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->postJson(route('relatorios.preditiva.buscar'), [
                'data_inicio' => $agora->toDateString(),
                'data_fim' => $agora->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('resumo.total', 2);

        $usuarios = collect($resposta->json('atividades'))->pluck('usuario')->sort()->values()->all();
        $this->assertSame(['N/A', $vendedorB->name], $usuarios);
        $this->assertNotContains($vendedorA->name, $usuarios);

        app(TabulationCatalog::class)->provision($empresaA->id);
        app(TabulationCatalog::class)->provision($empresaB->id);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $empresaB->id])
            ->getJson(route('relatorios.aproveitamento.dados', [
                'ano' => $agora->year,
                'mes_inicio' => $agora->month,
                'mes_fim' => $agora->month,
            ]))
            ->assertOk()
            ->assertJsonPath('dados.kpis.total_leads', 1);
    }

    public function test_filtros_de_vendedor_rejeitam_identificador_de_outra_empresa(): void
    {
        [, $empresaB, $master, $vendedorA] = $this->cenario();
        $sessao = [TenantContext::SESSION_KEY => $empresaB->id];

        foreach ([
            route('relatorios.preditiva.buscar') => [
                'data_inicio' => now()->toDateString(),
                'data_fim' => now()->toDateString(),
                'usuario_id' => $vendedorA->id,
            ],
        ] as $rota => $dados) {
            $this->actingAs($master)->withSession($sessao)
                ->postJson($rota, $dados)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('usuario_id');
        }

        foreach ([
            route('relatorios.performanceVendedor.dados'),
            route('relatorios.implantacoes.dados'),
            route('relatorios.desempenhoAnual.dados'),
        ] as $rota) {
            $this->actingAs($master)->withSession($sessao)
                ->getJson($rota.'?vendedor_id='.$vendedorA->id)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('vendedor_id');
        }
    }

    /** @return array{Empresa, Empresa, User, User, User} */
    private function cenario(): array
    {
        $empresaA = Empresa::factory()->create(['nome_fantasia' => 'Corretora A']);
        $empresaB = Empresa::factory()->create(['nome_fantasia' => 'Corretora B']);
        $master = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $vendedorA = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
            'name' => 'Vendedor A',
        ]);
        $vendedorB = User::factory()->create([
            'empresa_id' => $empresaB->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
            'name' => 'Vendedor B',
        ]);

        return [$empresaA, $empresaB, $master, $vendedorA, $vendedorB];
    }

    private function contato(int $empresaId, int $userId, string $nome): int
    {
        return DB::table('contatos')->insertGetId([
            'empresa_id' => $empresaId,
            'user_import_id' => $userId,
            'nome_cliente' => $nome,
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
