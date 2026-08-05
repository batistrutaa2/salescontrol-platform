<?php

namespace Tests\Feature;

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

        $dados = $this->actingAs($admin)->getJson(route('relatorios.distribuicaoLeads.dados', [
            'data_inicial' => $data->format('Y-m-d'),
            'data_final' => $data->format('Y-m-d'),
        ]))->assertOk()->json();

        $this->assertSame(3, $dados['resumo']['total_leads']);
        $this->assertSame(2, $dados['resumo']['leads_distribuidos']);
        $this->assertSame(1, $dados['resumo']['leads_nao_distribuidos']);
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
}
