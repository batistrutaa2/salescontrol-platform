<?php

namespace Tests\Feature\Backoffice;

use App\Enums\Tabulations;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\VendaDemanda;
use App\Models\VendaPortabilidade;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Painel operacional de processos: KPIs, prazo/atraso por SLA, atribuição de
 * responsável e conclusão. Gate para gestores (ADM/DEV/SUPERVISOR).
 */
class PainelProcessosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $gestor;

    private User $backoffice;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->gestor = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
        $this->backoffice = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::BACKOFFICE, 'ativo' => 'Y']);

        DB::table('tabulacoes')->insert([
            'id' => Tabulations::IMPLANTADO, 'empresa_id' => $this->empresa->id, 'descricao' => 'IMPLANTADO',
            'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function criarContrato(?Empresa $empresa = null): Vendas
    {
        $empresa = $empresa ?? $this->empresa;
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id, 'user_import_id' => $this->gestor->id, 'nome_cliente' => 'C '.uniqid(),
            'cpf' => (string) random_int(10000000000, 99999999999), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $empresa->id, 'user_id' => $this->gestor->id, 'contato_id' => $contatoId,
            'tabulacao_id' => Tabulations::IMPLANTADO, 'nome_contrato' => 'CONTRATO '.uniqid(),
            'cpf_cnpj' => (string) random_int(10000000000000, 99999999999999), 'operadora' => 'AMIL',
            'valor_contrato' => 500, 'vidas' => 1, 'data_vigencia' => now(),
        ]);
    }

    private function criarCancelamento(Vendas $venda, int $diasAtras): VendaDemanda
    {
        $d = VendaDemanda::create([
            'venda_id' => $venda->id, 'empresa_id' => $venda->empresa_id, 'created_by' => $this->gestor->id,
            'origem' => 'VENDEDOR', 'tipo' => TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value,
            'titulo' => 'Cancelamento', 'meta' => ['fase' => 'SOLICITADO'], 'status' => 'PENDENTE',
        ]);
        DB::table('venda_demandas')->where('id', $d->id)->update(['created_at' => now()->subDays($diasAtras)]);

        return $d->fresh();
    }

    public function test_data_traz_kpis_e_fila(): void
    {
        $venda = $this->criarContrato();
        $this->criarCancelamento($venda, 40); // SLA cancelamento = 30 -> atrasado
        VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'MARIA', 'sequencial' => 1]); // aberta, no prazo
        // Uma concluída neste mês (não entra na fila aberta, conta no KPI do mês).
        $c = $this->criarCancelamento($venda, 5);
        $c->update(['status' => 'CONCLUIDA', 'concluida_em' => now(), 'concluida_por' => $this->gestor->id]);

        $response = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'));

        $response->assertOk()->assertJson([
            'success' => true,
            'kpis' => [
                'cancelamentos_abertos' => 1,
                'portabilidades_abertas' => 1,
                'atrasados' => 1,
                'total_abertos' => 2,
                'concluidos_mes' => 1,
            ],
        ]);
        $this->assertCount(2, $response->json('fila'));
    }

    public function test_atraso_calculado_por_sla(): void
    {
        $venda = $this->criarContrato();
        $this->criarCancelamento($venda, 40); // atrasado
        $this->criarCancelamento($venda, 2);  // dentro do prazo

        $fila = collect($this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json('fila'));

        $this->assertSame(1, $fila->where('atrasado', true)->count());
        $this->assertSame(1, $fila->where('atrasado', false)->count());
        $this->assertGreaterThanOrEqual(9, $fila->firstWhere('atrasado', true)['dias_atraso']);
    }

    public function test_atribuir_responsavel(): void
    {
        $venda = $this->criarContrato();
        $cancel = $this->criarCancelamento($venda, 3);

        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.atribuir'), [
            'fonte' => 'demanda', 'id' => $cancel->id, 'responsavel_id' => $this->backoffice->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame($this->backoffice->id, $cancel->fresh()->responsavel_id);
    }

    public function test_concluir_processo_de_portabilidade(): void
    {
        $venda = $this->criarContrato();
        $port = VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'JOAO', 'sequencial' => 1]);

        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.concluir'), [
            'fonte' => 'portabilidade', 'id' => $port->id,
        ])->assertOk();

        $this->assertSame('CONCLUIDA', $port->fresh()->status);
        $this->assertNotNull($port->fresh()->concluida_em);
    }

    public function test_corte_esconde_processos_abertos_antes_da_data(): void
    {
        $venda = $this->criarContrato();

        // Antes do corte (config processos.corte_abertos = 01/05/2026): fora da fila, mesmo PENDENTE.
        $antigo = $this->criarCancelamento($venda, 1);
        DB::table('venda_demandas')->where('id', $antigo->id)->update(['created_at' => '2026-04-15 10:00:00']);
        $portAntiga = VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'ANTIGA', 'sequencial' => 1]);
        DB::table('vendas_portabilidades')->where('id', $portAntiga->id)->update(['created_at' => '2026-03-01 10:00:00']);

        // Depois do corte: aparece.
        $recente = $this->criarCancelamento($venda, 2);

        $json = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json();

        $ids = collect($json['fila'])->pluck('id');
        $this->assertTrue($ids->contains($recente->id));
        $this->assertFalse($ids->contains($antigo->id), 'Processo anterior ao corte não deve aparecer.');
        $this->assertSame(0, collect($json['fila'])->where('fonte', 'portabilidade')->count());
        $this->assertSame(1, $json['kpis']['total_abertos'], 'KPIs também respeitam o corte.');
    }

    public function test_paginacao_20_por_pagina(): void
    {
        $venda = $this->criarContrato();
        for ($i = 0; $i < 25; $i++) {
            $this->criarCancelamento($venda, 1);
        }

        $p1 = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json();
        $this->assertCount(20, $p1['fila']);
        $this->assertSame(25, $p1['paginacao']['total']);
        $this->assertSame(2, $p1['paginacao']['total_paginas']);
        $this->assertSame(1, $p1['paginacao']['de']);
        $this->assertSame(20, $p1['paginacao']['ate']);

        $p2 = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data').'?pagina=2')->json();
        $this->assertCount(5, $p2['fila']);
        $this->assertSame(21, $p2['paginacao']['de']);
        $this->assertSame(25, $p2['paginacao']['ate']);
    }

    public function test_fase_portabilidade_avanca_e_fase_final_encerra(): void
    {
        $venda = $this->criarContrato();
        $port = VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'MARIA', 'sequencial' => 1]);

        // Nasce em REUNINDO_DOCUMENTOS e aparece na fila com a fase.
        $fila = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json('fila');
        $this->assertSame('REUNINDO_DOCUMENTOS', collect($fila)->firstWhere('fonte', 'portabilidade')['fase_valor']);

        // Avança para análise — continua aberta.
        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.fasePortabilidade'), [
            'id' => $port->id, 'fase' => 'ENVIADA_ANALISE',
        ])->assertOk();
        $this->assertSame('PENDENTE', $port->fresh()->status);
        $this->assertSame('ENVIADA_ANALISE', $port->fresh()->fase);

        // NEGADA é final — encerra o processo com o desfecho registrado.
        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.fasePortabilidade'), [
            'id' => $port->id, 'fase' => 'NEGADA',
        ])->assertOk();
        $fresh = $port->fresh();
        $this->assertSame('CONCLUIDA', $fresh->status);
        $this->assertSame('NEGADA', $fresh->fase);
        $this->assertNotNull($fresh->concluida_em);
        $this->assertSame($this->gestor->id, $fresh->concluida_por);
    }

    public function test_fase_portabilidade_invalida_retorna_422(): void
    {
        $venda = $this->criarContrato();
        $port = VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'ZE', 'sequencial' => 1]);

        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.fasePortabilidade'), [
            'id' => $port->id, 'fase' => 'INEXISTENTE',
        ])->assertStatus(422);
    }

    public function test_backoffice_comum_nao_acessa_painel(): void
    {
        $this->actingAs($this->backoffice)->getJson(route('backoffice.painelProcessos.data'))->assertStatus(403);
    }

    public function test_multitenant_nao_mistura_empresas(): void
    {
        $venda = $this->criarContrato();
        $this->criarCancelamento($venda, 10);

        $outra = Empresa::factory()->create();
        $gestorOutra = User::factory()->create(['empresa_id' => $outra->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);

        $fila = $this->actingAs($gestorOutra)->getJson(route('backoffice.painelProcessos.data'))->json('fila');
        $this->assertCount(0, $fila);
    }
}
