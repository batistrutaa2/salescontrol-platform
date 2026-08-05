<?php

namespace Tests\Feature;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelatorioQualidadeVendasTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $supervisor;

    private User $developer;

    private User $backoffice;

    private User $vendedor;

    private User $vendedorDois;

    private int $contatoId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->admin = $this->usuario(UserRole::ADMINISTRATIVO, 'Administrativo');
        $this->supervisor = $this->usuario(UserRole::SUPERVISOR, 'Supervisor');
        $this->developer = $this->usuario(UserRole::DEVELOPER, 'Developer');
        $this->backoffice = $this->usuario(UserRole::BACKOFFICE, 'Backoffice');
        $this->vendedor = $this->usuario(UserRole::VENDEDOR, 'Ana Vendas');
        $this->vendedorDois = $this->usuario(UserRole::VENDEDOR, 'Bruno Vendas');

        foreach ([
            Tabulations::VENDA => 'VENDA',
            Tabulations::IMPLANTADO => 'IMPLANTADO',
            Tabulations::ESTORNO => 'ESTORNO',
            Tabulations::DECLINIO => 'DECLINIO',
        ] as $id => $descricao) {
            DB::table('tabulacoes')->insert([
                'id' => $id, 'empresa_id' => $this->empresa->id, 'descricao' => $descricao,
                'tipo_tabulacao' => 'A', 'efetivo' => $id === Tabulations::IMPLANTADO ? 'Y' : 'N',
                'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Contato do relatório',
            'cpf' => '12345678900',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_apenas_gestao_acessa_o_relatorio(): void
    {
        foreach ([$this->admin, $this->supervisor, $this->developer] as $user) {
            $this->actingAs($user)->get(route('relatorios.qualidadeVendas'))->assertOk();
        }

        $this->actingAs($this->vendedor)->get(route('relatorios.qualidadeVendas'))->assertForbidden();
        $this->actingAs($this->backoffice)->get(route('relatorios.qualidadeVendas'))->assertForbidden();
        $this->actingAs($this->vendedor)->getJson(route('relatorios.qualidadeVendas.dados', $this->filtros()))->assertForbidden();
    }

    public function test_calcula_valores_status_e_percentuais_pelas_regras_da_premiacao(): void
    {
        $this->venda($this->vendedor, Tabulations::IMPLANTADO, 1000, 100);
        $this->venda($this->vendedor, Tabulations::VENDA, 500, 50);
        $this->venda($this->vendedor, Tabulations::ESTORNO, 300, 30);
        $this->venda($this->vendedor, Tabulations::DECLINIO, 200, 20);

        $dados = $this->actingAs($this->admin)
            ->getJson(route('relatorios.qualidadeVendas.dados', $this->filtros()))
            ->assertOk()->json('dados');

        $this->assertSame(4, $dados['kpis']['total_propostas']);
        $this->assertEquals(2200.0, $dados['kpis']['valor_bruto']);
        $this->assertEquals(1980.0, $dados['kpis']['valor_valido']);
        $this->assertSame(1, $dados['kpis']['implantadas']);
        $this->assertSame(1, $dados['kpis']['em_processo']);
        $this->assertSame(1, $dados['kpis']['estornos']);
        $this->assertSame(1, $dados['kpis']['declinios']);
        $this->assertEquals(50.0, $dados['kpis']['percentual_implantacao']);
        $this->assertEquals(66.67, $dados['kpis']['percentual_implantacao_valor']);
        $this->assertEquals(50.0, $dados['kpis']['percentual_perda']);
        $this->assertEquals(
            $dados['kpis']['valor_bruto'],
            $dados['kpis']['valor_implantado'] + $dados['kpis']['valor_em_processo'] + $dados['kpis']['valor_estornado'] + $dados['kpis']['valor_declinado']
        );
    }

    public function test_ranking_exclui_declinio_mantem_estorno_e_respeita_excluir_ranking(): void
    {
        $this->venda($this->vendedor, Tabulations::IMPLANTADO, 1000);
        $this->venda($this->vendedor, Tabulations::ESTORNO, 700);
        $this->venda($this->vendedor, Tabulations::DECLINIO, 9000);
        $this->venda($this->vendedorDois, Tabulations::IMPLANTADO, 1500);

        $fora = $this->usuario(UserRole::VENDEDOR, 'Fora do ranking', true);
        $this->venda($fora, Tabulations::IMPLANTADO, 20000);

        $dados = $this->actingAs($this->admin)
            ->getJson(route('relatorios.qualidadeVendas.dados', $this->filtros()))
            ->assertOk()->json('dados');

        $ranking = $dados['rankings']['valor_valido'];
        $this->assertSame($this->vendedor->id, $ranking[0]['vendedor_id']);
        $this->assertEquals(1700.0, $ranking[0]['valor_valido']);
        $this->assertSame($this->vendedorDois->id, $ranking[1]['vendedor_id']);
        $this->assertNotContains($fora->id, collect($ranking)->pluck('vendedor_id')->all());
        $this->assertEquals(23200.0, $dados['kpis']['valor_valido']);
    }

    public function test_filtro_usa_data_da_venda_e_reflete_status_atual(): void
    {
        $venda = $this->venda($this->vendedor, Tabulations::IMPLANTADO, 800, 0, now()->startOfYear()->addDays(2));
        DB::table('vendas')->where('id', $venda->id)->update(['tabulacao_id' => Tabulations::ESTORNO]);
        $this->venda($this->vendedor, Tabulations::IMPLANTADO, 999, 0, now()->subYear());

        $dados = $this->actingAs($this->admin)
            ->getJson(route('relatorios.qualidadeVendas.dados', $this->filtros()))
            ->assertOk()->json('dados.kpis');

        $this->assertSame(1, $dados['total_propostas']);
        $this->assertSame(1, $dados['estornos']);
        $this->assertSame(0, $dados['implantadas']);
    }

    public function test_detalhamento_e_filtro_de_vendedor_nao_vazam_outra_empresa(): void
    {
        $minha = $this->venda($this->vendedor, Tabulations::ESTORNO, 300, 25);
        $this->venda($this->vendedorDois, Tabulations::IMPLANTADO, 500);

        $outraEmpresa = Empresa::factory()->create();
        $outroVendedor = User::factory()->create([
            'empresa_id' => $outraEmpresa->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y',
        ]);
        $contatoOutro = DB::table('contatos')->insertGetId([
            'empresa_id' => $outraEmpresa->id, 'user_import_id' => $outroVendedor->id,
            'nome_cliente' => 'Outro tenant', 'cpf' => '99999999999', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Vendas::create([
            'empresa_id' => $outraEmpresa->id, 'user_id' => $outroVendedor->id, 'contato_id' => $contatoOutro,
            'tabulacao_id' => Tabulations::ESTORNO, 'nome_contrato' => 'Não pode aparecer',
            'valor_contrato' => 99999, 'data_vigencia' => now(), 'created_at' => now(),
        ]);

        $params = array_merge($this->filtros(), ['vendedor_id' => $this->vendedor->id, 'categoria' => 'estorno']);
        $response = $this->actingAs($this->admin)
            ->getJson(route('relatorios.qualidadeVendas.propostas', $params))
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame($minha->id, $response->json('data.0.id'));
        $this->assertEquals(325.0, $response->json('data.0.valor_total'));
    }

    public function test_valida_datas_dentro_do_ano_atual(): void
    {
        $this->actingAs($this->admin)->getJson(route('relatorios.qualidadeVendas.dados', [
            'data_inicio' => (now()->year - 1).'-01-01',
            'data_fim' => now()->format('Y-m-d'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('data_inicio');

        $this->actingAs($this->admin)->getJson(route('relatorios.qualidadeVendas.dados', [
            'data_inicio' => now()->format('Y-m-d'),
            'data_fim' => now()->subDay()->format('Y-m-d'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('data_fim');
    }

    private function usuario(int $role, string $nome, bool $excluirRanking = false): User
    {
        return User::factory()->create([
            'empresa_id' => $this->empresa->id, 'user_role_id' => $role, 'name' => $nome,
            'ativo' => 'Y', 'excluir_ranking' => $excluirRanking,
        ]);
    }

    private function venda(User $vendedor, int $status, float $contrato, float $angariacao = 0, $data = null): Vendas
    {
        return Vendas::create([
            'empresa_id' => $this->empresa->id, 'user_id' => $vendedor->id,
            'contato_id' => $this->contatoId, 'tabulacao_id' => $status,
            'nome_contrato' => 'Cliente '.uniqid(), 'numero_proposta' => uniqid('PROP-'),
            'valor_contrato' => $contrato, 'angariacao_status' => $angariacao > 0 ? 'SIM' : 'NAO',
            'angariacao_valor' => $angariacao, 'data_vigencia' => now(),
            'data_implantacao' => $status === Tabulations::IMPLANTADO ? now() : null,
            'created_at' => $data ?? now(), 'updated_at' => $data ?? now(),
        ]);
    }

    private function filtros(): array
    {
        return ['data_inicio' => now()->startOfYear()->format('Y-m-d'), 'data_fim' => now()->endOfYear()->format('Y-m-d')];
    }
}
