<?php

namespace App\Services;

use App\Enums\TabulationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RelatorioAproveitamentoService
{
    private string $apiKey;

    private string $baseUrl;

    private string $model;

    private string $apiVersion;

    private int $maxTokens;

    public function __construct(private readonly TabulationCatalog $tabulationCatalog)
    {
        $this->apiKey = config('services.anthropic.api_key') ?? '';
        $this->baseUrl = rtrim((string) config('services.anthropic.base_url'), '/');
        $this->model = (string) config('services.anthropic.model');
        $this->apiVersion = (string) config('services.anthropic.version');
        $this->maxTokens = (int) config('services.anthropic.max_tokens');
    }

    /**
     * Coleta todos os dados agregados para o relatório
     */
    public function coletarDadosAgregados(int $ano, ?int $mesInicio = 1, ?int $mesFim = 12): array
    {
        $empresaId = app(\App\Support\TenantContext::class)->id();

        $dataInicio = Carbon::create($ano, $mesInicio, 1)->startOfMonth();
        $dataFim = Carbon::create($ano, $mesFim, 1)->endOfMonth();

        return [
            'periodo' => [
                'ano' => $ano,
                'mes_inicio' => $mesInicio,
                'mes_fim' => $mesFim,
                'data_inicio' => $dataInicio->format('d/m/Y'),
                'data_fim' => $dataFim->format('d/m/Y'),
            ],
            'leads_por_mes' => $this->getLeadsPorMes($empresaId, $ano, $mesInicio, $mesFim),
            'distribuicao_status' => $this->getDistribuicaoStatus($empresaId, $dataInicio, $dataFim),
            'distribuicao_temperatura' => $this->getDistribuicaoTemperatura($empresaId, $dataInicio, $dataFim),
            'vendas_por_mes' => $this->getVendasPorMes($empresaId, $ano, $mesInicio, $mesFim),
            'fontes_importacao' => $this->getFontesImportacao($empresaId, $dataInicio, $dataFim),
            'funil_conversao' => $this->getFunilConversao($empresaId, $dataInicio, $dataFim),
            'kpis' => $this->getKPIs($empresaId, $dataInicio, $dataFim),
        ];
    }

    /**
     * Leads importados por mês
     */
    private function getLeadsPorMes(int $empresaId, int $ano, int $mesInicio, int $mesFim): array
    {
        $resultado = DB::table('contatos')
            ->select(
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN is_ads = 'Y' THEN 1 ELSE 0 END) as ads"),
                DB::raw("SUM(CASE WHEN is_ads = 'N' OR is_ads IS NULL THEN 1 ELSE 0 END) as base")
            )
            ->where('empresa_id', $empresaId)
            ->whereYear('created_at', $ano)
            ->whereMonth('created_at', '>=', $mesInicio)
            ->whereMonth('created_at', '<=', $mesFim)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('mes')
            ->get();

        $meses = [];
        $nomesMeses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        for ($i = $mesInicio; $i <= $mesFim; $i++) {
            $dadosMes = $resultado->firstWhere('mes', $i);
            $meses[] = [
                'mes' => $i,
                'nome' => $nomesMeses[$i],
                'total' => $dadosMes->total ?? 0,
                'ads' => $dadosMes->ads ?? 0,
                'base' => $dadosMes->base ?? 0,
            ];
        }

        return $meses;
    }

    /**
     * Distribuição por status (tabulação)
     */
    private function getDistribuicaoStatus(int $empresaId, Carbon $dataInicio, Carbon $dataFim): array
    {
        return DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->join('tabulacoes as t', function ($join) {
                $join->on('cc.tabulacao_id', '=', 't.id')
                    ->on('cc.empresa_id', '=', 't.empresa_id');
            })
            ->select(
                't.descricao as status',
                't.tipo_tabulacao',
                't.efetivo',
                DB::raw('COUNT(*) as total')
            )
            ->where('c.empresa_id', $empresaId)
            ->where('cc.empresa_id', $empresaId)
            ->where('t.empresa_id', $empresaId)
            ->whereBetween('c.created_at', [$dataInicio, $dataFim])
            ->groupBy('t.id', 't.descricao', 't.tipo_tabulacao', 't.efetivo')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'tipo' => $item->tipo_tabulacao === 'C' ? 'Comercial' : 'Administrativo',
                    'efetivo' => $item->efetivo === 'Y',
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Distribuição por temperatura
     */
    private function getDistribuicaoTemperatura(int $empresaId, Carbon $dataInicio, Carbon $dataFim): array
    {
        $resultado = DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->select(
                'cc.temperatura',
                DB::raw('COUNT(*) as total')
            )
            ->where('c.empresa_id', $empresaId)
            ->where('cc.empresa_id', $empresaId)
            ->whereBetween('c.created_at', [$dataInicio, $dataFim])
            ->whereNotNull('cc.temperatura')
            ->groupBy('cc.temperatura')
            ->get();

        return [
            'quente' => $resultado->firstWhere('temperatura', 'QUENTE')->total ?? 0,
            'morno' => $resultado->firstWhere('temperatura', 'MORNO')->total ?? 0,
            'frio' => $resultado->firstWhere('temperatura', 'FRIO')->total ?? 0,
        ];
    }

    /**
     * Vendas por mês (cadastradas vs implantadas)
     */
    private function getVendasPorMes(int $empresaId, int $ano, int $mesInicio, int $mesFim): array
    {
        // Vendas cadastradas (pela data de criação)
        $cadastradas = DB::table('vendas')
            ->select(
                DB::raw('MONTH(created_at) as mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(valor_contrato) as valor_total'),
                DB::raw('SUM(vidas) as vidas_total')
            )
            ->where('empresa_id', $empresaId)
            ->whereYear('created_at', $ano)
            ->whereMonth('created_at', '>=', $mesInicio)
            ->whereMonth('created_at', '<=', $mesFim)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('mes')
            ->get();

        // Vendas implantadas (pela data de implantação)
        $implantadas = DB::table('vendas')
            ->select(
                DB::raw('MONTH(data_implantacao) as mes'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(valor_contrato) as valor_total'),
                DB::raw('SUM(vidas) as vidas_total')
            )
            ->where('empresa_id', $empresaId)
            ->whereNotNull('data_implantacao')
            ->whereYear('data_implantacao', $ano)
            ->whereMonth('data_implantacao', '>=', $mesInicio)
            ->whereMonth('data_implantacao', '<=', $mesFim)
            ->groupBy(DB::raw('MONTH(data_implantacao)'))
            ->orderBy('mes')
            ->get();

        $meses = [];
        $nomesMeses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        for ($i = $mesInicio; $i <= $mesFim; $i++) {
            $dadosCad = $cadastradas->firstWhere('mes', $i);
            $dadosImpl = $implantadas->firstWhere('mes', $i);
            $meses[] = [
                'mes' => $i,
                'nome' => $nomesMeses[$i],
                'cadastradas' => $dadosCad->total ?? 0,
                'cadastradas_valor' => (float) ($dadosCad->valor_total ?? 0),
                'implantadas' => $dadosImpl->total ?? 0,
                'implantadas_valor' => (float) ($dadosImpl->valor_total ?? 0),
                'vidas_total' => ($dadosImpl->vidas_total ?? 0),
            ];
        }

        return $meses;
    }

    /**
     * Fontes de importação (tipo_layout)
     */
    private function getFontesImportacao(int $empresaId, Carbon $dataInicio, Carbon $dataFim): array
    {
        return DB::table('contatos')
            ->select(
                DB::raw("COALESCE(tipo_layout, 'Não Informado') as fonte"),
                DB::raw('COUNT(*) as total')
            )
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->groupBy('tipo_layout')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'fonte' => $item->fonte,
                'total' => $item->total,
            ])
            ->toArray();
    }

    /**
     * Funil de conversão
     */
    private function getFunilConversao(int $empresaId, Carbon $dataInicio, Carbon $dataFim): array
    {
        $statusEmNegociacao = array_values($this->tabulationCatalog->requiredIds($empresaId, [
            TabulationCode::REUNIAO,
            TabulationCode::NEGOCIACAO,
            TabulationCode::DOCUMENTO,
        ]));
        $statusPerdidos = array_values($this->tabulationCatalog->requiredIds($empresaId, [
            TabulationCode::NEGOCIO_NAO_FECHADO,
            TabulationCode::ESTORNO,
            TabulationCode::DECLINADO,
        ]));

        // Total de leads importados no período
        $totalLeads = DB::table('contatos')
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->count();

        // Leads atribuídos a vendedores
        $leadsAtribuidos = DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->where('c.empresa_id', $empresaId)
            ->where('cc.empresa_id', $empresaId)
            ->whereBetween('c.created_at', [$dataInicio, $dataFim])
            ->whereNotNull('cc.user_id')
            ->count();

        // Leads em negociação (status comerciais ativos)
        $leadsEmNegociacao = DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->where('c.empresa_id', $empresaId)
            ->whereBetween('c.created_at', [$dataInicio, $dataFim])
            ->where('cc.empresa_id', $empresaId)
            ->whereIn('cc.tabulacao_id', $statusEmNegociacao)
            ->count();

        // Leads convertidos (vendas)
        $leadsConvertidos = DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->join('tabulacoes as t', function ($join) {
                $join->on('cc.tabulacao_id', '=', 't.id')
                    ->on('cc.empresa_id', '=', 't.empresa_id');
            })
            ->where('c.empresa_id', $empresaId)
            ->where('cc.empresa_id', $empresaId)
            ->where('t.empresa_id', $empresaId)
            ->whereBetween('c.created_at', [$dataInicio, $dataFim])
            ->where('t.efetivo', 'Y')
            ->count();

        // Leads perdidos
        $leadsPerdidos = DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->where('c.empresa_id', $empresaId)
            ->whereBetween('c.created_at', [$dataInicio, $dataFim])
            ->where('cc.empresa_id', $empresaId)
            ->whereIn('cc.tabulacao_id', $statusPerdidos)
            ->count();

        return [
            'importados' => $totalLeads,
            'atribuidos' => $leadsAtribuidos,
            'em_negociacao' => $leadsEmNegociacao,
            'convertidos' => $leadsConvertidos,
            'perdidos' => $leadsPerdidos,
            'taxa_atribuicao' => $totalLeads > 0 ? round(($leadsAtribuidos / $totalLeads) * 100, 1) : 0,
            'taxa_negociacao' => $leadsAtribuidos > 0 ? round(($leadsEmNegociacao / $leadsAtribuidos) * 100, 1) : 0,
            'taxa_conversao' => $totalLeads > 0 ? round(($leadsConvertidos / $totalLeads) * 100, 2) : 0,
        ];
    }

    /**
     * KPIs gerais
     */
    private function getKPIs(int $empresaId, Carbon $dataInicio, Carbon $dataFim): array
    {
        $negocioNaoFechadoId = $this->tabulationCatalog->id($empresaId, TabulationCode::NEGOCIO_NAO_FECHADO);
        // Total de leads
        $totalLeads = DB::table('contatos')
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->count();

        // Vendas cadastradas (pela data de criação)
        $vendasCadastradas = DB::table('vendas')
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->selectRaw('COUNT(*) as total, SUM(valor_contrato) as valor_total, SUM(vidas) as vidas_total')
            ->first();

        // Vendas implantadas (pela data de implantação) - GERA RECEITA
        $vendasImplantadas = DB::table('vendas')
            ->where('empresa_id', $empresaId)
            ->whereNotNull('data_implantacao')
            ->whereBetween('data_implantacao', [$dataInicio, $dataFim])
            ->selectRaw('COUNT(*) as total, SUM(valor_contrato) as valor_total, AVG(valor_contrato) as ticket_medio, SUM(vidas) as vidas_total')
            ->first();

        // Leads ativos (não convertidos e não perdidos)
        $leadsAtivos = DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->join('tabulacoes as t', function ($join) {
                $join->on('cc.tabulacao_id', '=', 't.id')
                    ->on('cc.empresa_id', '=', 't.empresa_id');
            })
            ->where('c.empresa_id', $empresaId)
            ->where('cc.empresa_id', $empresaId)
            ->where('t.empresa_id', $empresaId)
            ->whereBetween('c.created_at', [$dataInicio, $dataFim])
            ->where('t.efetivo', 'N')
            ->where('t.tipo_tabulacao', 'C')
            ->where('cc.tabulacao_id', '!=', $negocioNaoFechadoId)
            ->count();

        return [
            'total_leads' => $totalLeads,
            // Vendas cadastradas
            'vendas_cadastradas' => $vendasCadastradas->total ?? 0,
            'valor_cadastradas' => (float) ($vendasCadastradas->valor_total ?? 0),
            // Vendas implantadas (geram receita)
            'vendas_implantadas' => $vendasImplantadas->total ?? 0,
            'valor_implantadas' => (float) ($vendasImplantadas->valor_total ?? 0),
            'ticket_medio' => (float) ($vendasImplantadas->ticket_medio ?? 0),
            'vidas_total' => $vendasImplantadas->vidas_total ?? 0,
            'leads_ativos' => $leadsAtivos,
            'taxa_conversao' => $totalLeads > 0 ? round((($vendasImplantadas->total ?? 0) / $totalLeads) * 100, 2) : 0,
        ];
    }

    /**
     * Gera análise da IA com base nos dados agregados
     */
    public function gerarAnaliseIA(array $dados): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key da Anthropic não configurada. Adicione ANTHROPIC_API_KEY no arquivo .env',
            ];
        }

        $prompt = $this->construirPromptAnalise($dados);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post("{$this->baseUrl}/messages", [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $this->getSystemPrompt(),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? '';

                return [
                    'success' => true,
                    'analise' => $content,
                    'tokens_usados' => [
                        'input' => $data['usage']['input_tokens'] ?? 0,
                        'output' => $data['usage']['output_tokens'] ?? 0,
                    ],
                ];
            }

            Log::error('Falha do provedor de IA no relatório de aproveitamento.', [
                'status' => $response->status(),
                'request_id' => $response->header('request-id'),
            ]);

            return [
                'success' => false,
                'error' => 'O serviço de análise está temporariamente indisponível.',
            ];

        } catch (Throwable $e) {
            Log::error('Erro de conexão com o provedor de IA do relatório.', [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => 'O serviço de análise está temporariamente indisponível.',
            ];
        }
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Você é um analista de dados especializado em vendas de planos de saúde no Brasil. Sua função é analisar métricas de leads e vendas e fornecer insights acionáveis para melhorar a performance comercial.

## Suas responsabilidades:
1. Identificar tendências nos dados de importação e conversão
2. Detectar os melhores e piores períodos
3. Analisar o funil de vendas e identificar gargalos
4. Comparar performance entre diferentes fontes de leads
5. Sugerir ações práticas para melhorar resultados

## Formato da resposta:
Estruture sua análise em seções claras usando markdown:

### Resumo Executivo
Um parágrafo com os principais destaques do período.

### Análise de Performance
- Melhores meses e possíveis explicações
- Piores meses e possíveis causas
- Tendências identificadas

### Análise do Funil
- Taxa de conversão geral
- Gargalos identificados
- Comparação entre etapas

### Pontos Fortes
- O que está funcionando bem
- Práticas a manter

### Pontos de Atenção
- Problemas identificados
- Riscos potenciais

### Recomendações
- 3-5 ações práticas e específicas para melhorar os resultados
- Priorize por impacto potencial

Seja objetivo, use números quando relevante, e foque em insights acionáveis. Use português brasileiro.
PROMPT;
    }

    private function construirPromptAnalise(array $dados): string
    {
        $periodo = $dados['periodo'];
        $kpis = $dados['kpis'];
        $funil = $dados['funil_conversao'];

        // Formatar leads por mês
        $leadsMes = collect($dados['leads_por_mes'])
            ->map(fn ($m) => "{$m['nome']}: {$m['total']} leads (ADS: {$m['ads']}, Base: {$m['base']})")
            ->join("\n");

        // Formatar vendas por mês (cadastradas vs implantadas)
        $vendasMes = collect($dados['vendas_por_mes'])
            ->map(fn ($m) => "{$m['nome']}: Cadastradas: {$m['cadastradas']} (R$ ".number_format($m['cadastradas_valor'], 2, ',', '.').") | Implantadas: {$m['implantadas']} (R$ ".number_format($m['implantadas_valor'], 2, ',', '.').')')
            ->join("\n");

        // Formatar distribuição de status
        $statusDist = collect($dados['distribuicao_status'])
            ->take(10)
            ->map(fn ($s) => "- {$s['status']}: {$s['total']} leads")
            ->join("\n");

        // Formatar fontes
        $fontes = collect($dados['fontes_importacao'])
            ->map(fn ($f) => "- {$f['fonte']}: {$f['total']} leads")
            ->join("\n");

        // Temperatura
        $temp = $dados['distribuicao_temperatura'];

        return <<<PROMPT
## Período Analisado
De {$periodo['data_inicio']} a {$periodo['data_fim']}

## KPIs Gerais
- Total de Leads Importados: {$kpis['total_leads']}
- Vendas Cadastradas: {$kpis['vendas_cadastradas']} (R$ {$this->formatarMoeda($kpis['valor_cadastradas'])})
- Vendas Implantadas (geram receita): {$kpis['vendas_implantadas']} (R$ {$this->formatarMoeda($kpis['valor_implantadas'])})
- Ticket Médio: R$ {$this->formatarMoeda($kpis['ticket_medio'])}
- Total de Vidas: {$kpis['vidas_total']}
- Taxa de Conversão Geral: {$kpis['taxa_conversao']}%

IMPORTANTE: Há diferença entre "cadastradas" e "implantadas":
- Cadastradas: vendas registradas no sistema (propostas)
- Implantadas: vendas efetivadas que geram receita para a empresa

## Leads Importados por Mês
{$leadsMes}

## Vendas por Mês (Cadastradas vs Implantadas)
{$vendasMes}

## Funil de Conversão
- Leads Importados: {$funil['importados']}
- Leads Atribuídos a Vendedores: {$funil['atribuidos']} ({$funil['taxa_atribuicao']}%)
- Leads em Negociação: {$funil['em_negociacao']} ({$funil['taxa_negociacao']}% dos atribuídos)
- Leads Convertidos (Vendas): {$funil['convertidos']} ({$funil['taxa_conversao']}% do total)
- Leads Perdidos: {$funil['perdidos']}

## Distribuição por Temperatura
- Quente: {$temp['quente']} leads
- Morno: {$temp['morno']} leads
- Frio: {$temp['frio']} leads

## Top 10 Status Atuais
{$statusDist}

## Fontes de Importação
{$fontes}

---

Com base nesses dados, forneça uma análise completa seguindo o formato solicitado. Dê atenção especial à diferença entre vendas cadastradas e implantadas, pois é um indicador importante da saúde do funil de vendas.
PROMPT;
    }

    private function formatarMoeda(float $valor): string
    {
        return number_format($valor, 2, ',', '.');
    }
}
