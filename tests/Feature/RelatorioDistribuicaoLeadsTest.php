<?php

namespace Tests\Feature;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RelatorioDistribuicaoLeadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consolida_cobertura_e_ranking_sem_vazar_ou_cortar_o_ultimo_dia(): void
    {
        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
        $vendedor = User::factory()->create(['empresa_id' => $empresa->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y', 'name' => 'Ana Comercial']);

        $comercial = DB::table('tabulacoes')->insertGetId([
            'empresa_id' => $empresa->id, 'descricao' => 'PROSPECÇÃO', 'tipo_tabulacao' => 'C',
            'efetivo' => 'N', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $administrativo = DB::table('tabulacoes')->insertGetId([
            'empresa_id' => $empresa->id, 'descricao' => 'VENDA', 'tipo_tabulacao' => 'A',
            'efetivo' => 'Y', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $data = now()->setTime(23, 30);
        $contatos = collect(['Lead comercial', 'Lead administrativo', 'Lead sem distribuição'])->map(fn ($nome) => DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id, 'user_import_id' => $vendedor->id, 'nome_cliente' => $nome,
            'status' => 'Y', 'created_at' => $data, 'updated_at' => $data,
        ]));
        DB::table('contatos')->insert([
            'empresa_id' => $outraEmpresa->id, 'user_import_id' => $vendedor->id, 'nome_cliente' => 'Outra empresa',
            'status' => 'Y', 'created_at' => $data, 'updated_at' => $data,
        ]);
        DB::table('contatos_corretores')->insert([
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos[0], 'user_id' => $vendedor->id, 'tabulacao_id' => $comercial, 'created_at' => $data, 'updated_at' => $data],
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos[1], 'user_id' => $vendedor->id, 'tabulacao_id' => $administrativo, 'created_at' => $data, 'updated_at' => $data],
        ]);
        DB::table('lead_reservatorio_itens')->insert([
            'empresa_id' => $empresa->id,
            'contato_id' => $contatos[2],
            'origem' => 'IMPORTACAO',
            'status' => 'DISPONIVEL',
            'entrou_por' => $admin->id,
            'entrou_em' => $data,
            'created_at' => $data,
            'updated_at' => $data,
        ]);

        $dados = $this->actingAs($admin)->getJson(route('relatorios.distribuicaoLeads.dados', [
            'data_inicial' => $data->format('Y-m-d'),
            'data_final' => $data->format('Y-m-d'),
        ]))->assertOk()->json();

        $this->assertSame(3, $dados['resumo']['total_leads']);
        $this->assertSame(2, $dados['resumo']['leads_distribuidos']);
        $this->assertSame(1, $dados['resumo']['leads_nao_distribuidos']);
        $this->assertSame(1, $dados['resumo']['leads_reservatorio']);
        $this->assertSame(0, $dados['resumo']['leads_fila_implantacao']);
        $this->assertEquals(66.7, $dados['resumo']['cobertura_distribuicao']);
        $this->assertSame('Ana Comercial', $dados['ranking_vendedores'][0]['name']);
        $this->assertEquals(2, $dados['ranking_vendedores'][0]['total']);
        $this->assertEquals(3, $dados['evolucao'][0]['total']);
    }

    public function test_rejeita_periodo_invertido(): void
    {
        DB::table('user_roles')->insert([
            'id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);

        $this->actingAs($admin)->getJson(route('relatorios.distribuicaoLeads.dados', [
            'data_inicial' => '2026-08-05', 'data_final' => '2026-08-04',
        ]))->assertUnprocessable()->assertJsonValidationErrors('data_final');
    }

    public function test_separa_trabalho_comercial_filas_e_saidas_do_processo_administrativo(): void
    {
        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
        $vendedor = User::factory()->create(['empresa_id' => $empresa->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y', 'name' => 'Vendedor da base']);
        $agora = now();

        DB::table('tabulacoes')->insert([
            ['id' => Tabulations::PROSPECCAO, 'empresa_id' => $empresa->id, 'descricao' => 'PROSPECÇÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'N', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $agora, 'updated_at' => $agora],
            ['id' => Tabulations::REMARKETING, 'empresa_id' => $empresa->id, 'descricao' => 'REMARKETING', 'tipo_tabulacao' => 'C', 'efetivo' => 'N', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $agora, 'updated_at' => $agora],
            ['id' => Tabulations::VENDA, 'empresa_id' => $empresa->id, 'descricao' => 'VENDA', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $agora, 'updated_at' => $agora],
            ['id' => Tabulations::ESTORNO, 'empresa_id' => $empresa->id, 'descricao' => 'ESTORNO', 'tipo_tabulacao' => 'A', 'efetivo' => 'N', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $agora, 'updated_at' => $agora],
            ['id' => Tabulations::IMPLANTADO, 'empresa_id' => $empresa->id, 'descricao' => 'IMPLANTADO', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $agora, 'updated_at' => $agora],
            ['id' => Tabulations::DECLINIO, 'empresa_id' => $empresa->id, 'descricao' => 'DECLINADO', 'tipo_tabulacao' => 'A', 'efetivo' => 'N', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $agora, 'updated_at' => $agora],
        ]);

        $nomes = ['Comercial', 'Remarketing', 'Preditiva', 'Reservatório', 'Órfão fora do reservatório', 'Fila administrativa', 'Carteira', 'Declinado', 'Estornado', 'Descartado'];
        $contatos = collect($nomes)->mapWithKeys(function (string $nome) use ($empresa, $vendedor, $agora): array {
            $id = DB::table('contatos')->insertGetId([
                'empresa_id' => $empresa->id,
                'user_import_id' => $vendedor->id,
                'nome_cliente' => $nome,
                'status' => $nome === 'Descartado' ? 'N' : 'Y',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

            return [$nome => $id];
        });

        DB::table('contatos_corretores')->insert([
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Comercial'], 'user_id' => $vendedor->id, 'tabulacao_id' => Tabulations::PROSPECCAO, 'created_at' => $agora, 'updated_at' => $agora],
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Remarketing'], 'user_id' => $vendedor->id, 'tabulacao_id' => Tabulations::REMARKETING, 'created_at' => $agora, 'updated_at' => $agora],
        ]);
        DB::table('preditiva')->insert([
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Preditiva'], 'status' => 'Y', 'created_at' => $agora, 'updated_at' => $agora],
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Preditiva'], 'status' => 'Y', 'created_at' => $agora, 'updated_at' => $agora],
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Comercial'], 'status' => 'N', 'created_at' => $agora, 'updated_at' => $agora],
        ]);
        DB::table('lead_reservatorio_itens')->insert([
            'empresa_id' => $empresa->id,
            'contato_id' => $contatos['Reservatório'],
            'origem' => 'IMPORTACAO',
            'status' => 'DISPONIVEL',
            'entrou_por' => $admin->id,
            'entrou_em' => $agora,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        foreach ([
            'Fila administrativa' => Tabulations::VENDA,
            'Carteira' => Tabulations::IMPLANTADO,
            'Declinado' => Tabulations::DECLINIO,
            'Estornado' => Tabulations::ESTORNO,
        ] as $nome => $tabulacaoId) {
            DB::table('vendas')->insert([
                'empresa_id' => $empresa->id,
                'user_id' => $vendedor->id,
                'contato_id' => $contatos[$nome],
                'tabulacao_id' => $tabulacaoId,
                'nome_contrato' => $nome,
                'data_vigencia' => $agora->toDateString(),
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }

        $dados = $this->actingAs($admin)->getJson(route('relatorios.distribuicaoLeads.dados', [
            'data_inicial' => $agora->format('Y-m-d'),
            'data_final' => $agora->format('Y-m-d'),
        ]))->assertOk()->json();

        $this->assertSame(10, $dados['resumo']['total_leads']);
        $this->assertSame(1, $dados['resumo']['leads_comercial']);
        $this->assertSame(1, $dados['resumo']['leads_preditiva']);
        $this->assertSame(1, $dados['resumo']['leads_remarketing']);
        $this->assertSame(1, $dados['resumo']['leads_reservatorio']);
        $this->assertSame(1, $dados['resumo']['leads_sem_atribuicao']);
        $this->assertSame(2, $dados['resumo']['leads_viraram_venda']);
        $this->assertSame(1, $dados['resumo']['leads_fila_implantacao']);
        $this->assertSame(1, $dados['resumo']['leads_carteira_clientes']);
        $this->assertSame(1, $dados['resumo']['leads_declinados']);
        $this->assertSame(1, $dados['resumo']['leads_estornados']);
        $this->assertSame('VENDA', $dados['distribuicao_administrativa'][0]['descricao']);
        $this->assertEquals(1, $dados['ranking_vendedores'][0]['comercial']);
        $this->assertEquals(1, $dados['ranking_vendedores'][0]['remarketing']);
        $this->assertSame(1, $dados['ranking_vendedores'][0]['administrativo']);

        $detalhes = $this->actingAs($admin)->getJson(route('relatorios.distribuicaoLeads.vendedorDetalhes', [
            'vendedor' => $vendedor->id,
            'data_inicial' => $agora->format('Y-m-d'),
            'data_final' => $agora->format('Y-m-d'),
        ]))->assertOk()->json();

        $this->assertSame($vendedor->id, $detalhes['vendedor']['id']);
        $this->assertSame(1, $detalhes['total_fila_comercial']);
        $this->assertSame('PROSPECÇÃO', $detalhes['fila_comercial'][0]['descricao']);
        $this->assertEquals(1, $detalhes['fila_comercial'][0]['total']);
        $this->assertSame(0, $detalhes['viraram_venda']);

        $outraEmpresa = Empresa::factory()->create();
        $vendedorOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $this->actingAs($admin)
            ->getJson(route('relatorios.distribuicaoLeads.vendedorDetalhes', $vendedorOutraEmpresa->id))
            ->assertNotFound();
    }

    public function test_detalhe_mostra_status_atual_dos_leads_enviados_e_so_conta_contrato_cadastrado_no_periodo_uma_vez(): void
    {
        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create(['empresa_id' => $empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
        $vendedor = User::factory()->create(['empresa_id' => $empresa->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y']);
        $hoje = now()->startOfDay()->addHours(10);
        $ontem = $hoje->copy()->subDay();
        $cadastroAntigo = $hoje->copy()->subMonths(2);

        DB::table('tabulacoes')->insert([
            ['id' => Tabulations::PROSPECCAO, 'empresa_id' => $empresa->id, 'descricao' => 'PROSPECÇÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'N', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $hoje, 'updated_at' => $hoje],
            ['id' => Tabulations::NEGOCIO_FECHADO, 'empresa_id' => $empresa->id, 'descricao' => 'NEGOCIO FECHADO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $hoje, 'updated_at' => $hoje],
            ['id' => Tabulations::VENDA, 'empresa_id' => $empresa->id, 'descricao' => 'VENDA', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $hoje, 'updated_at' => $hoje],
            ['id' => Tabulations::NOVOS_CLIENTES, 'empresa_id' => $empresa->id, 'descricao' => 'NOVOS CLIENTES', 'tipo_tabulacao' => 'C', 'efetivo' => 'N', 'status' => 'Y', 'sub_tabulacao' => 'N', 'created_at' => $hoje, 'updated_at' => $hoje],
        ]);
        $statusConfiguravel = DB::table('tabulacoes')->insertGetId([
            'empresa_id' => $empresa->id,
            'descricao' => 'PROSPECTADO',
            'tipo_tabulacao' => 'C',
            'efetivo' => 'N',
            'status' => 'Y',
            'sub_tabulacao' => 'N',
            'created_at' => $hoje,
            'updated_at' => $hoje,
        ]);

        $contatos = collect(['Prospectado', 'Novo', 'Fechado sem contrato', 'Enviado fora do período'])
            ->mapWithKeys(function (string $nome) use ($empresa, $vendedor, $cadastroAntigo): array {
                $id = DB::table('contatos')->insertGetId([
                    'empresa_id' => $empresa->id,
                    'user_import_id' => $vendedor->id,
                    'nome_cliente' => $nome,
                    'status' => 'Y',
                    'created_at' => $cadastroAntigo,
                    'updated_at' => $cadastroAntigo,
                ]);

                return [$nome => $id];
            });

        DB::table('contatos_corretores')->insert([
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Prospectado'], 'user_id' => $vendedor->id, 'tabulacao_id' => $statusConfiguravel, 'created_at' => $hoje, 'updated_at' => $hoje],
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Novo'], 'user_id' => $vendedor->id, 'tabulacao_id' => Tabulations::NOVOS_CLIENTES, 'created_at' => $hoje, 'updated_at' => $hoje],
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Fechado sem contrato'], 'user_id' => $vendedor->id, 'tabulacao_id' => Tabulations::NEGOCIO_FECHADO, 'created_at' => $hoje, 'updated_at' => $hoje],
            ['empresa_id' => $empresa->id, 'contato_id' => $contatos['Enviado fora do período'], 'user_id' => $vendedor->id, 'tabulacao_id' => Tabulations::PROSPECCAO, 'created_at' => $ontem, 'updated_at' => $ontem],
        ]);

        foreach ([
            [$contatos['Prospectado'], $hoje, 'Contrato 1'],
            [$contatos['Prospectado'], $hoje->copy()->addMinute(), 'Contrato duplicado do mesmo lead'],
            [$contatos['Novo'], $ontem, 'Contrato fora do período'],
            [$contatos['Enviado fora do período'], $hoje, 'Lead não enviado no período'],
        ] as [$contatoId, $criadoEm, $nomeContrato]) {
            DB::table('vendas')->insert([
                'empresa_id' => $empresa->id,
                'user_id' => $vendedor->id,
                'contato_id' => $contatoId,
                'tabulacao_id' => Tabulations::VENDA,
                'nome_contrato' => $nomeContrato,
                'data_vigencia' => $hoje->toDateString(),
                'created_at' => $criadoEm,
                'updated_at' => $criadoEm,
            ]);
        }

        $parametros = [
            'vendedor' => $vendedor->id,
            'data_inicial' => $hoje->toDateString(),
            'data_final' => $hoje->toDateString(),
        ];
        $detalhes = $this->actingAs($admin)
            ->getJson(route('relatorios.distribuicaoLeads.vendedorDetalhes', $parametros))
            ->assertOk()
            ->json();

        $status = collect($detalhes['fila_comercial'])->pluck('total', 'descricao');
        $this->assertSame(3, $detalhes['total_fila_comercial']);
        $this->assertEquals(1, $status['PROSPECTADO']);
        $this->assertEquals(1, $status['NOVOS CLIENTES']);
        $this->assertEquals(1, $status['NEGOCIO FECHADO']);
        $this->assertArrayNotHasKey('PROSPECÇÃO', $status->all());
        $this->assertSame(1, $detalhes['viraram_venda']);

        $ranking = $this->actingAs($admin)
            ->getJson(route('relatorios.distribuicaoLeads.dados', [
                'data_inicial' => $hoje->toDateString(),
                'data_final' => $hoje->toDateString(),
            ]))
            ->assertOk()
            ->json('ranking_vendedores.0');
        $this->assertEquals(3, $ranking['total']);
        $this->assertEquals(3, $ranking['comercial']);
    }
}
