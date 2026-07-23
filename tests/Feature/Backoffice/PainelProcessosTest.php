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
 * Painel operacional de processos (esteira kanban): o prazo conta a partir da
 * IMPLANTAÇÃO do contrato — antes disso o processo fica "aguardando implantação"
 * e não conta atraso. KPIs de urgência, atribuição/conclusão e fase por trilha.
 * Gate para gestores (ADM/DEV/SUPERVISOR).
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

    private function criarContrato(?Empresa $empresa = null, ?string $dataImplantacao = null): Vendas
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
            'valor_contrato' => 500, 'vidas' => 1, 'data_vigencia' => now(), 'data_implantacao' => $dataImplantacao,
        ]);
    }

    /** Contrato ainda NÃO implantado (tabulação não-implantada, sem data). */
    private function criarContratoNaoImplantado(): Vendas
    {
        $tabId = Tabulations::VENDA; // 16 — pós-venda, ainda não implantado
        if (! DB::table('tabulacoes')->where('id', $tabId)->exists()) {
            DB::table('tabulacoes')->insert([
                'id' => $tabId, 'empresa_id' => $this->empresa->id, 'descricao' => 'EM ANÁLISE',
                'tipo_tabulacao' => 'A', 'efetivo' => 'N', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id, 'user_import_id' => $this->gestor->id, 'nome_cliente' => 'C '.uniqid(),
            'cpf' => (string) random_int(10000000000, 99999999999), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $this->empresa->id, 'user_id' => $this->gestor->id, 'contato_id' => $contatoId,
            'tabulacao_id' => $tabId, 'nome_contrato' => 'CONTRATO '.uniqid(),
            'cpf_cnpj' => (string) random_int(10000000000000, 99999999999999), 'operadora' => 'AMIL',
            'valor_contrato' => 500, 'vidas' => 1, 'data_vigencia' => now(),
        ]);
    }

    private function criarCancelamento(Vendas $venda, int $diasAtras, string $fase = 'SOLICITADO'): VendaDemanda
    {
        $d = VendaDemanda::create([
            'venda_id' => $venda->id, 'empresa_id' => $venda->empresa_id, 'created_by' => $this->gestor->id,
            'origem' => 'VENDEDOR', 'tipo' => TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value,
            'titulo' => 'Cancelamento', 'meta' => ['fase' => $fase], 'status' => 'PENDENTE',
        ]);
        DB::table('venda_demandas')->where('id', $d->id)->update(['created_at' => now()->subDays($diasAtras)]);

        return $d->fresh();
    }

    public function test_data_traz_kpis_e_fila(): void
    {
        $venda = $this->criarContrato();
        $this->criarCancelamento($venda, 40); // implantado (relógio na abertura) · SLA 30 -> atrasado
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
                'aguardando_implantacao' => 0,
                'concluidos_mes' => 1,
            ],
        ]);
        $this->assertCount(2, $response->json('fila'));
        // Payload novo expõe fluxos das duas trilhas p/ o board montar as colunas.
        $this->assertNotEmpty($response->json('fases_cancelamento'));
        $this->assertNotEmpty($response->json('fases_portabilidade'));
    }

    public function test_atraso_calculado_por_sla(): void
    {
        // Contratos distintos p/ não agrupar (o painel agrupa por contrato+tipo).
        $this->criarCancelamento($this->criarContrato(), 40); // atrasado
        $this->criarCancelamento($this->criarContrato(), 2);  // dentro do prazo

        $fila = collect($this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json('fila'));

        $this->assertSame(1, $fila->where('atrasado', true)->count());
        $this->assertSame(1, $fila->where('atrasado', false)->count());
        $this->assertGreaterThanOrEqual(9, $fila->firstWhere('atrasado', true)['dias_atraso']);
    }

    public function test_prazo_conta_a_partir_da_implantacao(): void
    {
        // Contrato implantado há 40 dias, cancelamento aberto HOJE: relógio começou
        // na implantação -> já atrasado (SLA 30), mesmo recém-criado.
        $vAtrasado = $this->criarContrato(null, now()->subDays(40)->format('Y-m-d'));
        $this->criarCancelamento($vAtrasado, 0);

        // Implantado há 25 dias -> vence em ~5 dias -> "vencendo".
        $vVencendo = $this->criarContrato(null, now()->subDays(25)->format('Y-m-d'));
        $this->criarCancelamento($vVencendo, 0);

        $json = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json();
        $fila = collect($json['fila']);

        $this->assertSame('atrasado', $fila->firstWhere('venda_id', $vAtrasado->id)['urgencia']);
        $this->assertSame('vencendo', $fila->firstWhere('venda_id', $vVencendo->id)['urgencia']);
        $this->assertSame(1, $json['kpis']['atrasados']);
        $this->assertSame(1, $json['kpis']['vencendo']);
    }

    public function test_processo_bloqueado_quando_contrato_nao_implantado(): void
    {
        $venda = $this->criarContratoNaoImplantado();
        $cancel = $this->criarCancelamento($venda, 10); // aberto, mas contrato sem implantar

        $json = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json();
        $item = collect($json['fila'])->firstWhere('id', $cancel->id);

        $this->assertTrue($item['bloqueado']);
        $this->assertFalse($item['atrasado'], 'Sem implantação, não conta atraso.');
        $this->assertSame('aguardando_implantacao', $item['urgencia']);
        $this->assertSame('EM ANÁLISE', $item['situacao_contrato']);
        $this->assertNull($item['vence_em']);
        // KPIs: fora de atrasados, dentro de aguardando implantação.
        $this->assertSame(0, $json['kpis']['atrasados']);
        $this->assertSame(1, $json['kpis']['aguardando_implantacao']);
    }

    public function test_caso_raro_fase_avancada_sem_implantacao_nao_bloqueia(): void
    {
        // Portou/cancelou ANTES de implantar (raro): fase já além da 1ª -> não bloqueia.
        $venda = $this->criarContratoNaoImplantado();
        $cancel = $this->criarCancelamento($venda, 2, 'PROTOCOLADO');

        $item = collect($this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json('fila'))
            ->firstWhere('id', $cancel->id);

        $this->assertFalse($item['bloqueado']);
        $this->assertNotSame('aguardando_implantacao', $item['urgencia']);
        $this->assertNotNull($item['vence_em']);
    }

    public function test_fase_cancelamento_avanca_e_fase_final_conclui(): void
    {
        $venda = $this->criarContrato();
        $cancel = $this->criarCancelamento($venda, 2);

        // Avança para protocolado — continua aberto.
        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.faseCancelamento'), [
            'id' => $cancel->id, 'fase' => 'PROTOCOLADO',
        ])->assertOk()->assertJson(['success' => true]);
        $this->assertSame('PENDENTE', $cancel->fresh()->status);
        $this->assertSame('PROTOCOLADO', $cancel->fresh()->meta['fase']);

        // CONCLUIDO é final — encerra o processo.
        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.faseCancelamento'), [
            'id' => $cancel->id, 'fase' => 'CONCLUIDO',
        ])->assertOk();
        $fresh = $cancel->fresh();
        $this->assertSame('CONCLUIDA', $fresh->status);
        $this->assertNotNull($fresh->concluida_em);
    }

    public function test_fase_cancelamento_invalida_422_e_gate_403(): void
    {
        $venda = $this->criarContrato();
        $cancel = $this->criarCancelamento($venda, 2);

        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.faseCancelamento'), [
            'id' => $cancel->id, 'fase' => 'INEXISTENTE',
        ])->assertStatus(422);

        $this->actingAs($this->backoffice)->postJson(route('backoffice.painelProcessos.faseCancelamento'), [
            'id' => $cancel->id, 'fase' => 'PROTOCOLADO',
        ])->assertStatus(403);
    }

    public function test_aba_concluidos_lista_baixados_do_mes(): void
    {
        $venda = $this->criarContrato();

        // Cancelamento concluído neste mês.
        $c = $this->criarCancelamento($venda, 5);
        $c->update(['status' => 'CONCLUIDA', 'concluida_em' => now(), 'concluida_por' => $this->gestor->id]);

        // Portabilidade NEGADA neste mês (desfecho negativo, mas concluída).
        $p = VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'MARIA', 'sequencial' => 1]);
        $p->update(['status' => 'CONCLUIDA', 'fase' => 'NEGADA', 'concluida_em' => now(), 'concluida_por' => $this->gestor->id]);

        $json = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json();

        // Não entram na fila aberta; entram na lista de concluídos.
        $this->assertCount(0, $json['fila']);
        $concluidos = collect($json['concluidos']);
        $this->assertCount(2, $concluidos);

        $canc = $concluidos->firstWhere('grupo', 'cancelamentos');
        $this->assertSame('Concluído', $canc['desfecho']);
        $this->assertSame($this->gestor->name, $canc['por']);

        $port = $concluidos->firstWhere('grupo', 'portabilidade');
        $this->assertSame('Negada', $port['desfecho']);
    }

    public function test_concluidos_ordenados_por_conclusao_desc(): void
    {
        $venda = $this->criarContrato();
        $concluir = function (string $nome, $quando) use ($venda) {
            $d = $this->criarCancelamento($venda, 5);
            DB::table('venda_demandas')->where('id', $d->id)->update([
                'titulo' => $nome, 'status' => 'CONCLUIDA', 'concluida_em' => $quando, 'concluida_por' => $this->gestor->id,
            ]);
        };
        $concluir('A', now()->subDays(10));
        $concluir('B', now()->subDays(1)); // mais recente -> deve vir primeiro
        $concluir('C', now()->subDays(5));

        $lista = collect($this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json('concluidos'));

        $ts = $lista->pluck('ts')->all();
        $this->assertSame(collect($ts)->sortDesc()->values()->all(), $ts, 'Concluídos devem vir do mais recente para o mais antigo.');
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

    public function test_painel_foca_em_cancelamento_e_portabilidade(): void
    {
        $venda = $this->criarContrato();
        $cancel = $this->criarCancelamento($venda, 2);
        VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'MARIA', 'sequencial' => 1]);

        // Outros tipos do checklist (boas-vindas, boleto) NÃO entram no painel.
        foreach ([TipoDemandaContrato::BOAS_VINDAS, TipoDemandaContrato::ENVIO_BOLETO] as $tipo) {
            VendaDemanda::create([
                'venda_id' => $venda->id, 'empresa_id' => $venda->empresa_id, 'created_by' => $this->gestor->id,
                'origem' => 'BACKOFFICE', 'tipo' => $tipo->value, 'titulo' => $tipo->value, 'status' => 'PENDENTE',
            ]);
        }

        $json = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json();

        $this->assertCount(2, $json['fila'], 'Só cancelamento + portabilidade entram.');
        $tipos = collect($json['fila'])->pluck('tipo');
        $this->assertTrue($tipos->contains($cancel->tipo));
        $this->assertTrue($tipos->contains('PORTABILIDADE'));
        $this->assertFalse($tipos->contains(TipoDemandaContrato::BOAS_VINDAS->value));
        $this->assertFalse($tipos->contains(TipoDemandaContrato::ENVIO_BOLETO->value));
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
        $this->assertCount(1, $json['fila'], 'Fila também respeita o corte.');
    }

    public function test_sem_paginacao_retorna_todos_os_processos(): void
    {
        // Sem paginação: todos os itens (limitados pelo corte) voltam de uma vez.
        // Contratos distintos p/ não agrupar entre si.
        for ($i = 0; $i < 25; $i++) {
            $this->criarCancelamento($this->criarContrato(), 1);
        }

        $json = $this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json();

        $this->assertCount(25, $json['fila']);
        $this->assertArrayNotHasKey('paginacao', $json);
    }

    public function test_agrupa_processos_do_mesmo_contrato_em_um_registro(): void
    {
        $venda = $this->criarContrato();
        // Dois cancelamentos (por titular) do MESMO contrato+tipo.
        $this->criarCancelamento($venda, 10);
        $this->criarCancelamento($venda, 40);
        // Duas portabilidades do mesmo contrato.
        VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'MARIA', 'sequencial' => 1]);
        VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'JOAO', 'sequencial' => 2]);

        $fila = collect($this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json('fila'));

        // 1 registro de cancelamento + 1 de portabilidade (não 4).
        $this->assertCount(2, $fila);
        $canc = $fila->firstWhere('grupo', 'cancelamentos');
        $this->assertSame(2, $canc['qtd']);
        $this->assertTrue($canc['atrasado'], 'Representa pelo mais urgente (o de 40 dias).');
        $this->assertSame(2, $fila->firstWhere('grupo', 'portabilidade')['qtd']);
    }

    public function test_acao_de_fase_vale_para_todos_os_titulares_do_contrato(): void
    {
        $venda = $this->criarContrato();
        $a = $this->criarCancelamento($venda, 5);
        $b = $this->criarCancelamento($venda, 5);

        // Concluir pelo painel usando o id representativo do grupo.
        $rep = collect($this->actingAs($this->gestor)->getJson(route('backoffice.painelProcessos.data'))->json('fila'))->first();
        $this->actingAs($this->gestor)->postJson(route('backoffice.painelProcessos.faseCancelamento'), [
            'id' => $rep['id'], 'fase' => 'CONCLUIDO',
        ])->assertOk();

        // Ambos os titulares concluídos, não só o representativo.
        $this->assertSame('CONCLUIDA', $a->fresh()->status);
        $this->assertSame('CONCLUIDA', $b->fresh()->status);
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
