<?php

namespace App\Http\Controllers\pages\relatorios;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\LeadReservatorioItem;
use App\Models\LogPreditiva;
use App\Models\User;
use App\Models\Vendas as VendasModel;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\LigacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Relatorios extends Controller
{
    protected LigacoesRepository $ligacoesRepository;

    protected UsuariosRepository $usuariosRepository;

    protected ContatosCorretoresRepository $contatosCorretoresRepository;

    public function __construct(
        LigacoesRepositoryInterface $ligacoesRepositoryInterface,
        UsuariosRepositoryInterface $usuariosRepositoryInterface,
        ContatosCorretoresRepositoryInterface $ContatosCorretoresRepositoryInterface
    ) {
        $this->ligacoesRepository = $ligacoesRepositoryInterface;
        $this->usuariosRepository = $usuariosRepositoryInterface;
        $this->contatosCorretoresRepository = $ContatosCorretoresRepositoryInterface;
    }

    public function index()
    {
        $user = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);

        return view('content.pages.relatorios.ligacoes', [
            'users' => $user,
        ]);
    }

    public function getLigacoes($id_user, $data_inicial, $data_final)
    {
        $ligacoes = $this->ligacoesRepository->getLigacoes($id_user, $data_inicial, $data_final);
        $filaAtual = $this->contatosCorretoresRepository->getQueueCurrent($id_user);

        return response()->json([
            'ligacoes' => $ligacoes,
            'fila' => $filaAtual,
        ]);
    }

    public function predictiveReport()
    {
        $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);

        return view('content.pages.relatorios.preditiva', [
            'usuarios' => $users,
        ]);
    }

    public function get(Request $request)
    {
        $dataInicio = Carbon::parse($request->data_inicio)->startOfDay();
        $dataFim = Carbon::parse($request->data_fim)->endOfDay();
        $usuarioId = $request->usuario_id;
        $empresaId = auth()->user()->empresa_id; // Assumindo que o usuário logado tem empresa_id

        // Consulta base nos logs de preditiva
        $query = LogPreditiva::whereBetween('created_at', [$dataInicio, $dataFim])
            ->where('empresa_id', $empresaId);

        if ($usuarioId) {
            $query->where('user_id', $usuarioId);
        }

        // Obter logs com relacionamentos
        $logs = $query->with(['user', 'contato'])->get();

        // Preparar dados para a tabela
        $dadosTabela = $logs->map(function ($log) {
            return [
                'data' => $log->created_at->format('d/m/Y H:i'),
                'usuario' => $log->user->name ?? 'N/A',
                'cliente' => $log->contato->nome_cliente ?? 'N/A',
                'telefone' => $log->contato->telefone1 ?? 'N/A',
                'status' => $log->acao,
                'tabulacao' => $log->tabulacao,
                'observacao' => '', // Não há campo de observação no log_preditiva, então deixamos vazio
            ];
        });

        // Calcular resumo
        $total = $logs->count();
        $convertidos = $logs->where('acao', 'CONVERSAO')->count();
        $cotacoes = $logs->where('tabulacao', 'COTACAO')->count();
        $ligarDepois = $logs->where('tabulacao', 'LIGAR_DEPOIS')->count();
        $descartados = $logs->where('acao', 'DESCARTE')->count();
        $taxaConversao = $total > 0 ? round(($convertidos / $total) * 100, 1).'%' : '0%';

        // Preparar dados para gráfico diário
        $periodoCompleto = collect(new \DatePeriod(
            $dataInicio,
            new \DateInterval('P1D'),
            $dataFim->addDay() // Adicionamos um dia para incluir o último dia no período
        ))->map(function ($date) {
            return $date->format('Y-m-d');
        });

        $logsPorDia = $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        });

        $dadosGraficoDiario = [
            'datas' => $periodoCompleto->map(function ($data) {
                return Carbon::parse($data)->format('d/m');
            })->toArray(),
            'contatos' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
                return $logsPorDia->get($data, collect())->count();
            })->toArray(),
            'convertidos' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
                return $logsPorDia->get($data, collect())->where('acao', 'CONVERSAO')->count();
            })->toArray(),
            'cotacoes' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
                return $logsPorDia->get($data, collect())->where('tabulacao', 'COTACAO')->count();
            })->toArray(),
            'ligar_depois' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
                return $logsPorDia->get($data, collect())->where('tabulacao', 'LIGAR_DEPOIS')->count();
            })->toArray(),
            'descartados' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
                return $logsPorDia->get($data, collect())->where('acao', 'DESCARTE')->count();
            })->toArray(),
        ];

        // Preparar dados de desempenho por vendedor
        $logsPorVendedor = $logs->groupBy(function ($log) {
            return $log->user->name ?? 'N/A';
        });

        $vendedorNomes = [];
        $vendedorConvertidos = [];
        $vendedorCotacoes = [];
        $vendedorLigarDepois = [];
        $vendedorDescartados = [];
        $vendedorTotal = [];
        $vendedorTaxa = [];

        foreach ($logsPorVendedor as $nome => $logsVendedor) {
            $vendedorNomes[] = $nome;
            $conv = $logsVendedor->where('acao', 'CONVERSAO')->count();
            $cot = $logsVendedor->where('tabulacao', 'COTACAO')->count();
            $lig = $logsVendedor->where('tabulacao', 'LIGAR_DEPOIS')->count();
            $desc = $logsVendedor->where('acao', 'DESCARTE')->count();
            $tot = $logsVendedor->count();
            $vendedorConvertidos[] = $conv;
            $vendedorCotacoes[] = $cot;
            $vendedorLigarDepois[] = $lig;
            $vendedorDescartados[] = $desc;
            $vendedorTotal[] = $tot;
            $vendedorTaxa[] = $tot > 0 ? round(($conv / $tot) * 100, 1) : 0;
        }

        // Ordenar por taxa de conversão decrescente
        $indices = range(0, count($vendedorNomes) - 1);
        usort($indices, function ($a, $b) use ($vendedorTaxa) {
            return $vendedorTaxa[$b] <=> $vendedorTaxa[$a];
        });

        $dadosGraficoVendedores = [
            'nomes' => array_map(fn ($i) => $vendedorNomes[$i], $indices),
            'convertidos' => array_map(fn ($i) => $vendedorConvertidos[$i], $indices),
            'cotacoes' => array_map(fn ($i) => $vendedorCotacoes[$i], $indices),
            'ligar_depois' => array_map(fn ($i) => $vendedorLigarDepois[$i], $indices),
            'descartados' => array_map(fn ($i) => $vendedorDescartados[$i], $indices),
            'total' => array_map(fn ($i) => $vendedorTotal[$i], $indices),
            'taxa' => array_map(fn ($i) => $vendedorTaxa[$i], $indices),
        ];

        // Adicionar dados de tabulação
        $tabulacaoCount = $logs->groupBy('tabulacao')->map->count();
        $dadosGraficoTabulacao = [
            'labels' => $tabulacaoCount->keys()->toArray(),
            'valores' => $tabulacaoCount->values()->toArray(),
        ];

        return response()->json([
            'success' => true,
            'atividades' => $dadosTabela,
            'resumo' => [
                'total' => $total,
                'convertidos' => $convertidos,
                'cotacoes' => $cotacoes,
                'ligar_depois' => $ligarDepois,
                'descartados' => $descartados,
                'taxa_conversao' => $taxaConversao,
            ],
            'grafico_diario' => $dadosGraficoDiario,
            'grafico_vendedores' => $dadosGraficoVendedores,
            'grafico_tabulacao' => $dadosGraficoTabulacao,
        ]);
    }

    public function performanceVendedor()
    {
        $empresaId = Auth::user()->empresa_id;

        $vendedores = User::where('empresa_id', $empresaId)
            ->select('id', 'name')
            ->where('user_role_id', 1)
            ->where('ativo', 'Y')
            ->orderBy('name')
            ->get();

        return view('content.pages.relatorios.performance-vendedor', [
            'vendedores' => $vendedores,
        ]);
    }

    public function performanceVendedorData(Request $request)
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $vendedorId = $request->get('vendedor_id');

            if (! $vendedorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendedor não informado.',
                ], 422);
            }

            $vendedor = User::where('empresa_id', $empresaId)->find($vendedorId);

            if (! $vendedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendedor não encontrado.',
                ], 404);
            }

            $anoAtual = (int) date('Y');
            $anoFoco = (int) $request->get('ano', $anoAtual);

            // Tempo de casa derivado dos dados (1ª venda/atividade -> ano atual)
            $primeiroAno = $this->getPrimeiroAnoAtividade($empresaId, $vendedorId, $anoFoco);
            $ultimoAno = max($anoAtual, $anoFoco);

            // Quando o ano foco é o ano corrente (ainda em andamento), comparamos o
            // MESMO período em todos os anos (ex.: Jan 1 -> hoje) para não confrontar
            // um período parcial contra um ano inteiro do passado.
            $parcial = $anoFoco >= $anoAtual;
            $cutoffMesDia = $parcial ? date('m-d') : null;
            $mesCorrente = (int) date('n');

            // Série anual da carreira (base da curva e da tabela YoY)
            $evolucaoAnual = $this->getEvolucaoAnualVendedor($empresaId, $vendedorId, $primeiroAno, $ultimoAno, $cutoffMesDia);

            // KPIs: ano foco vs ano anterior (mesmo período quando parcial)
            $kpis = $this->getKpisYoY($evolucaoAnual, $anoFoco);

            // Comparativo mensal: ano foco vs ano anterior
            $comparativoMensal = $this->getComparativoMensal($empresaId, $vendedorId, $anoFoco, $parcial ? $mesCorrente : null);

            // Diagnóstico / inteligência
            $diagnostico = $this->getDiagnosticoPerformance($evolucaoAnual, $anoFoco);

            return response()->json([
                'success' => true,
                'data' => [
                    'vendedor' => [
                        'id' => $vendedor->id,
                        'name' => $vendedor->name,
                        'primeiro_ano' => $primeiroAno,
                        'ultimo_ano' => $ultimoAno,
                        'anos_de_trabalho' => max(1, $ultimoAno - $primeiroAno + 1),
                    ],
                    'ano_foco' => $anoFoco,
                    'periodo' => [
                        'parcial' => $parcial,
                        'label' => $parcial ? 'até '.date('d/m') : 'ano completo',
                    ],
                    'kpis' => $kpis,
                    'evolucao_anual' => array_values($evolucaoAnual),
                    'comparativo_mensal' => $comparativoMensal,
                    'diagnostico' => $diagnostico,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conjunto de tabulações que caracterizam uma venda válida ("conta como cadastrado").
     * ESTORNO (17) e DECLINADO (53) ficam de fora de propósito: o próprio enum os
     * classifica como "não conta como cadastrado", então não inflam a performance.
     */
    private function tabulacoesVendaValida(): array
    {
        return [
            Tabulations::VENDA,
            Tabulations::IMPLANTADO,
            Tabulations::PENDENCIA,
            Tabulations::ANALISE_OPERADORA,
            Tabulations::BOLETO_DISPONIVEL,
            Tabulations::REGULARIZADO,
            Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
            Tabulations::ANALISE_DOCUMENTOS,
            Tabulations::AGUARD_ASSINATURA_DS,
        ];
    }

    /**
     * Expressão SQL do valor vendido (contrato + angariação quando aplicável).
     */
    private function valorVendidoExpr(): string
    {
        return 'valor_contrato + CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END';
    }

    /**
     * Limita a query ao mesmo período (Jan 1 -> mês-dia) em cada ano, garantindo
     * comparações ano a ano "maçã com maçã" quando o ano foco ainda está em andamento.
     * O formato 'm-d' é zero-padded, então a comparação lexicográfica é segura.
     */
    private function aplicarCutoffMesDia($query, ?string $cutoffMesDia): void
    {
        if ($cutoffMesDia) {
            $query->whereRaw("DATE_FORMAT(created_at, '%m-%d') <= ?", [$cutoffMesDia]);
        }
    }

    private function getPrimeiroAnoAtividade($empresaId, $vendedorId, $fallbackAno): int
    {
        $anoVenda = DB::table('vendas')
            ->where('user_id', $vendedorId)
            ->min(DB::raw('YEAR(created_at)'));

        $anoLead = DB::table('lead_atividades')
            ->where('empresa_id', $empresaId)
            ->where('user_id', $vendedorId)
            ->min(DB::raw('YEAR(created_at)'));

        $anos = array_filter([$anoVenda, $anoLead]);

        return $anos ? (int) min($anos) : (int) $fallbackAno;
    }

    private function getEvolucaoAnualVendedor($empresaId, $vendedorId, $primeiroAno, $ultimoAno, ?string $cutoffMesDia = null): array
    {
        $tabs = $this->tabulacoesVendaValida();
        $valorExpr = $this->valorVendidoExpr();

        $vendasQuery = VendasModel::query()
            ->where('user_id', $vendedorId)
            ->whereHas('contatoCorretor', fn ($q) => $q->whereIn('tabulacao_id', $tabs));
        $this->aplicarCutoffMesDia($vendasQuery, $cutoffMesDia);

        $vendasPorAno = $vendasQuery
            ->selectRaw("YEAR(created_at) as ano, COUNT(*) as total_vendas, SUM($valorExpr) as valor_vendido, SUM(vidas) as vidas")
            ->groupBy(DB::raw('YEAR(created_at)'))
            ->get()
            ->keyBy('ano');

        $leadsQuery = DB::table('lead_atividades')
            ->where('empresa_id', $empresaId)
            ->where('user_id', $vendedorId);
        $this->aplicarCutoffMesDia($leadsQuery, $cutoffMesDia);

        $leadsPorAno = $leadsQuery
            ->selectRaw('YEAR(created_at) as ano, COUNT(DISTINCT contato_id) as leads')
            ->groupBy(DB::raw('YEAR(created_at)'))
            ->get()
            ->keyBy('ano');

        $serie = [];
        $valorAnterior = null;

        for ($ano = $primeiroAno; $ano <= $ultimoAno; $ano++) {
            $v = $vendasPorAno->get($ano);
            $totalVendas = $v ? (int) $v->total_vendas : 0;
            $valorVendido = $v ? (float) $v->valor_vendido : 0.0;
            $vidas = $v ? (int) $v->vidas : 0;
            $l = $leadsPorAno->get($ano);
            $leads = $l ? (int) $l->leads : 0;

            $taxaConversao = $leads > 0 ? round(($totalVendas / $leads) * 100, 2) : 0;
            $ticketMedio = $totalVendas > 0 ? round($valorVendido / $totalVendas, 2) : 0;
            $crescimento = ($valorAnterior !== null && $valorAnterior > 0)
                ? round((($valorVendido - $valorAnterior) / $valorAnterior) * 100, 1)
                : null;

            $serie[$ano] = [
                'ano' => $ano,
                'valor_vendido' => round($valorVendido, 2),
                'total_vendas' => $totalVendas,
                'leads_trabalhados' => $leads,
                'vidas' => $vidas,
                'taxa_conversao' => $taxaConversao,
                'ticket_medio' => $ticketMedio,
                'crescimento_valor_pct' => $crescimento,
            ];

            $valorAnterior = $valorVendido;
        }

        return $serie;
    }

    private function getKpisYoY(array $evolucaoAnual, int $anoFoco): array
    {
        $atual = $evolucaoAnual[$anoFoco] ?? $this->linhaAnualVazia($anoFoco);
        $anterior = $evolucaoAnual[$anoFoco - 1] ?? $this->linhaAnualVazia($anoFoco - 1);

        $metricas = ['valor_vendido', 'total_vendas', 'leads_trabalhados', 'taxa_conversao', 'vidas', 'ticket_medio'];
        $kpis = [];

        foreach ($metricas as $m) {
            $kpis[$m] = $this->montaKpi($atual[$m], $anterior[$m]);
        }

        return $kpis;
    }

    private function montaKpi($atual, $anterior): array
    {
        $atual = (float) $atual;
        $anterior = (float) $anterior;

        if ($anterior > 0) {
            $variacao = round((($atual - $anterior) / $anterior) * 100, 1);
        } elseif ($atual > 0) {
            $variacao = 100.0;
        } else {
            $variacao = null;
        }

        $direcao = 'flat';
        if ($variacao !== null) {
            if ($variacao > 0) {
                $direcao = 'up';
            } elseif ($variacao < 0) {
                $direcao = 'down';
            }
        }

        return [
            'atual' => $atual,
            'anterior' => $anterior,
            'variacao_pct' => $variacao,
            'direcao' => $direcao,
        ];
    }

    private function linhaAnualVazia(int $ano): array
    {
        return [
            'ano' => $ano,
            'valor_vendido' => 0,
            'total_vendas' => 0,
            'leads_trabalhados' => 0,
            'vidas' => 0,
            'taxa_conversao' => 0,
            'ticket_medio' => 0,
            'crescimento_valor_pct' => null,
        ];
    }

    private function getComparativoMensal($empresaId, $vendedorId, int $anoFoco, ?int $mesLimiteFoco = null): array
    {
        $tabs = $this->tabulacoesVendaValida();
        $valorExpr = $this->valorVendidoExpr();
        $anoAnterior = $anoFoco - 1;

        $vendas = VendasModel::query()
            ->where('user_id', $vendedorId)
            ->whereHas('contatoCorretor', fn ($q) => $q->whereIn('tabulacao_id', $tabs))
            ->whereRaw('YEAR(created_at) IN (?, ?)', [$anoFoco, $anoAnterior])
            ->selectRaw("YEAR(created_at) as ano, MONTH(created_at) as mes, COUNT(*) as total_vendas, SUM($valorExpr) as valor")
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        $leads = DB::table('lead_atividades')
            ->where('empresa_id', $empresaId)
            ->where('user_id', $vendedorId)
            ->whereRaw('YEAR(created_at) IN (?, ?)', [$anoFoco, $anoAnterior])
            ->selectRaw('YEAR(created_at) as ano, MONTH(created_at) as mes, COUNT(DISTINCT contato_id) as leads')
            ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'))
            ->get();

        $mapVenda = [];
        foreach ($vendas as $row) {
            $mapVenda[$row->ano][$row->mes] = (float) $row->valor;
        }

        $mapLead = [];
        foreach ($leads as $row) {
            $mapLead[$row->ano][$row->mes] = (int) $row->leads;
        }

        $nomesMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $meses = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            // Meses futuros do ano foco (quando ano corrente) ficam nulos para a linha
            // simplesmente parar, em vez de despencar para zero.
            $foraDoPeriodo = $mesLimiteFoco !== null && $mes > $mesLimiteFoco;

            $meses[] = [
                'mes' => $mes,
                'nome_mes' => $nomesMeses[$mes - 1],
                'valor_ano_foco' => $foraDoPeriodo ? null : ($mapVenda[$anoFoco][$mes] ?? 0),
                'valor_ano_anterior' => $mapVenda[$anoAnterior][$mes] ?? 0,
                'leads_ano_foco' => $foraDoPeriodo ? null : ($mapLead[$anoFoco][$mes] ?? 0),
                'leads_ano_anterior' => $mapLead[$anoAnterior][$mes] ?? 0,
            ];
        }

        return [
            'ano_foco' => $anoFoco,
            'ano_anterior' => $anoAnterior,
            'meses' => $meses,
        ];
    }

    private function getDiagnosticoPerformance(array $evolucaoAnual, int $anoFoco): array
    {
        $serie = array_values($evolucaoAnual);
        $resumo = [];

        // Melhor ano em valor vendido
        $melhorAno = null;
        $melhorValor = -1;
        foreach ($serie as $linha) {
            if ($linha['valor_vendido'] > $melhorValor) {
                $melhorValor = $linha['valor_vendido'];
                $melhorAno = $linha['ano'];
            }
        }

        // Tendência via inclinação (regressão linear simples sobre valor vendido)
        $tendencia = 'estavel';
        $n = count($serie);
        if ($n >= 2) {
            $valores = array_column($serie, 'valor_vendido');
            $media = array_sum($valores) / $n;
            $slope = $this->slopeLinear($valores);
            if ($media > 0) {
                $slopeRel = $slope / $media;
                if ($slopeRel > 0.05) {
                    $tendencia = 'crescimento';
                } elseif ($slopeRel < -0.05) {
                    $tendencia = 'queda';
                }
            }
        }

        // Nível textual a partir da tendência + tempo de casa
        if ($tendencia === 'crescimento') {
            $nivel = 'Em ascensão';
        } elseif ($tendencia === 'queda') {
            $nivel = 'Em queda';
        } elseif ($n >= 3) {
            $nivel = 'Consolidado';
        } else {
            $nivel = 'Em desenvolvimento';
        }

        $atual = $evolucaoAnual[$anoFoco] ?? null;
        $anterior = $evolucaoAnual[$anoFoco - 1] ?? null;

        if ($atual && $anterior && $anterior['valor_vendido'] > 0) {
            $diff = round((($atual['valor_vendido'] - $anterior['valor_vendido']) / $anterior['valor_vendido']) * 100, 1);
            $verbo = $diff >= 0 ? 'Cresceu' : 'Caiu';
            $resumo[] = "{$verbo} ".abs($diff)."% em valor vendido vs {$anterior['ano']}.";
        }

        if ($melhorAno !== null && $melhorValor > 0) {
            $resumo[] = "Melhor ano: {$melhorAno} (R$ ".number_format($melhorValor, 2, ',', '.').').';
        }

        if ($atual && $anterior) {
            $deltaConv = round($atual['taxa_conversao'] - $anterior['taxa_conversao'], 1);
            if ($deltaConv != 0) {
                $verbo = $deltaConv > 0 ? 'subiu' : 'caiu';
                $resumo[] = "Conversão {$verbo} ".abs($deltaConv)." p.p. vs {$anterior['ano']}.";
            }

            $deltaLeads = $atual['leads_trabalhados'] - $anterior['leads_trabalhados'];
            if ($deltaLeads != 0) {
                $verbo = $deltaLeads > 0 ? 'mais' : 'menos';
                $resumo[] = 'Trabalhou '.abs($deltaLeads)." leads {$verbo} que em {$anterior['ano']}.";
            }
        }

        if (empty($resumo)) {
            $resumo[] = 'Sem histórico suficiente para comparação ano a ano.';
        }

        return [
            'tendencia' => $tendencia,
            'melhor_ano' => $melhorAno,
            'melhor_ano_valor' => max(0, $melhorValor),
            'nivel' => $nivel,
            'resumo' => $resumo,
        ];
    }

    /**
     * Inclinação (slope) de uma regressão linear simples y = a + b*x, com x = 1..n.
     */
    private function slopeLinear(array $valores): float
    {
        $n = count($valores);
        if ($n < 2) {
            return 0.0;
        }

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($valores as $i => $y) {
            $x = $i + 1;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denom = ($n * $sumX2) - ($sumX * $sumX);
        if ($denom == 0) {
            return 0.0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denom;
    }

    public function implantacoes()
    {
        return view('content.pages.relatorios.implantacoes');
    }

    public function implantacoesData(Request $request)
    {
        $filtros = $this->aplicarFiltrosImplantacao($request);

        return response()->json([
            'success' => true,
            'data' => [
                'vendas_totais' => $this->getImplantacoesTotais($filtros),
                'vendas_por_mes' => $this->getImplantacoesPorMes($filtros),
                'vendas_por_vendedor' => $this->getImplantacoesPorVendedor($filtros),
                'vendas_por_operadora' => $this->getImplantacoesPorOperadora($filtros),
                'vendas_por_plano' => $this->getImplantacoesPorPlano($filtros),
                'resumo_geral' => $this->getResumoGeralImplantacao($filtros),
                'vendedores' => $this->getVendedoresPorEmpresa(),
                'anos_disponiveis' => $this->getAnosDisponiveisImplantacao($filtros),
                'operadoras' => $this->getOperadoras($filtros),
            ],
        ]);
    }

    public function implantacoesList(Request $request)
    {
        $filtros = $this->aplicarFiltrosImplantacao($request);
        $perPage = $request->get('per_page', 20);
        $vendas = $this->getImplantacoesTotais($filtros, $perPage);

        return response()->json([
            'success' => true,
            'data' => $vendas->items(),
            'pagination' => [
                'current_page' => $vendas->currentPage(),
                'last_page' => $vendas->lastPage(),
                'per_page' => $vendas->perPage(),
                'total' => $vendas->total(),
                'from' => $vendas->firstItem(),
                'to' => $vendas->lastItem(),
            ],
        ]);
    }

    private function aplicarFiltrosImplantacao($request)
    {
        return [
            'ano' => $request->get('ano'),
            'mes' => $request->get('mes'),
            'vendedor_id' => $request->get('vendedor_id'),
            'operadora' => $request->get('operadora'),
            'data_inicio' => $request->get('data_inicio'),
            'data_fim' => $request->get('data_fim'),
            'empresa_id' => Auth::user()->empresa_id,
        ];
    }

    private function aplicarFiltroEmpresa($query)
    {
        $empresaId = Auth::user()->empresa_id;

        return $query->whereHas('user', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        });
    }

    private function aplicarFiltroStatusImplantado($query)
    {
        return $query->whereHas('contatoCorretor', function ($q) {
            $q->where('tabulacao_id', Tabulations::IMPLANTADO);
        });
    }

    private function aplicarFiltroStatusVenda($query)
    {
        return $query->whereHas('contatoCorretor', function ($q) {
            $q->whereIn('tabulacao_id', [
                Tabulations::VENDA,
                Tabulations::IMPLANTADO,
                Tabulations::ESTORNO,
                Tabulations::PENDENCIA,
                Tabulations::ANALISE_OPERADORA,
                Tabulations::BOLETO_DISPONIVEL,
                Tabulations::REGULARIZADO,
                Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                Tabulations::ANALISE_DOCUMENTOS,
                Tabulations::AGUARD_ASSINATURA_DS,
            ]);
        });
    }

    private function getImplantacoesTotais($filtros, $perPage = null)
    {
        $query = VendasModel::with([
            'user' => function ($query) {
                $query->select('id', 'name', 'empresa_id');
            },
        ]);

        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusImplantado($query);

        // Filtra por data de implantação, não por data de criação
        if ($filtros['ano']) {
            $query->whereYear('vendas.data_implantacao', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.data_implantacao', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.data_implantacao', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        $query->orderBy('vendas.data_implantacao', 'desc');

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    private function getImplantacoesPorMes($filtros)
    {
        $query = VendasModel::select(
            DB::raw('MONTH(vendas.data_implantacao) as mes'),
            DB::raw('YEAR(vendas.data_implantacao) as ano'),
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw('SUM(valor_contrato) as valor_total'),
            DB::raw('SUM(vidas) as total_vidas')
        );

        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusImplantado($query);

        if ($filtros['ano']) {
            $query->whereYear('vendas.data_implantacao', $filtros['ano']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.data_implantacao', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return $query->groupBy('ano', 'mes')
            ->orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->get();
    }

    private function getImplantacoesPorVendedor($filtros)
    {
        $empresaId = Auth::user()->empresa_id;

        $query = VendasModel::select(
            'users.name as vendedor',
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw('SUM(valor_contrato) as valor_total'),
            DB::raw('SUM(vidas) as total_vidas')
        )->join('users', 'vendas.user_id', '=', 'users.id')
            ->where('users.empresa_id', $empresaId);

        if ($filtros['ano']) {
            $query->whereYear('vendas.data_implantacao', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.data_implantacao', $filtros['mes']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.data_implantacao', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        $query = $this->aplicarFiltroStatusImplantado($query);

        return $query->groupBy('users.id', 'users.name')
            ->orderBy('valor_total', 'desc')
            ->get();
    }

    private function getImplantacoesPorOperadora($filtros)
    {
        $query = VendasModel::select(
            'operadora',
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw('SUM(valor_contrato) as valor_total'),
            DB::raw('SUM(vidas) as total_vidas')
        );

        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusImplantado($query);

        if ($filtros['ano']) {
            $query->whereYear('vendas.data_implantacao', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.data_implantacao', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.data_implantacao', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return $query->whereNotNull('operadora')
            ->groupBy('operadora')
            ->orderBy('valor_total', 'desc')
            ->get();
    }

    private function getImplantacoesPorPlano($filtros)
    {
        $query = VendasModel::select(
            'nome_plano',
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw('SUM(valor_contrato) as valor_total')
        );

        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusImplantado($query);

        if ($filtros['ano']) {
            $query->whereYear('vendas.data_implantacao', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.data_implantacao', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.data_implantacao', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return $query->whereNotNull('nome_plano')
            ->groupBy('nome_plano')
            ->orderBy('total_vendas', 'desc')
            ->get();
    }

    private function getResumoGeralImplantacao($filtros)
    {
        $query = VendasModel::query();

        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusImplantado($query);

        if ($filtros['ano']) {
            $query->whereYear('vendas.data_implantacao', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.data_implantacao', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.data_implantacao', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        // Query para vendas cadastradas no mesmo período (mantém created_at)
        $queryCadastradas = VendasModel::query();
        $queryCadastradas = $this->aplicarFiltroEmpresa($queryCadastradas);
        $queryCadastradas = $this->aplicarFiltroStatusVenda($queryCadastradas);

        if ($filtros['ano']) {
            $queryCadastradas->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $queryCadastradas->whereMonth('vendas.created_at', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $queryCadastradas->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $queryCadastradas->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $queryCadastradas->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return [
            'total_contratos' => $query->count(),
            'valor_total' => $query->sum('valor_contrato') ?? 0,
            'valor_cadastrado' => $queryCadastradas->sum('valor_contrato') ?? 0,
            'total_vidas' => $query->sum('vidas') ?? 0,
            'ticket_medio' => $query->avg('valor_contrato') ?? 0,
            'vidas_por_contrato' => $query->avg('vidas') ?? 0,
        ];
    }

    private function getVendedoresPorEmpresa()
    {
        return User::where('empresa_id', Auth::user()->empresa_id)
            ->where('user_role_id', UserRole::VENDEDOR)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function getAnosDisponiveisImplantacao($filtros)
    {
        $query = VendasModel::select(DB::raw('YEAR(COALESCE(data_implantacao, vendas.created_at)) as ano'));

        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusImplantado($query);

        return $query->distinct()
            ->whereNotNull(DB::raw('YEAR(COALESCE(data_implantacao, vendas.created_at))'))
            ->orderBy('ano', 'desc')
            ->pluck('ano');
    }

    private function getOperadoras($filtros)
    {
        $query = VendasModel::select('operadora');

        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusImplantado($query);

        return $query->whereNotNull('operadora')
            ->where('operadora', '!=', '')
            ->distinct()
            ->orderBy('operadora')
            ->pluck('operadora');
    }

    public function desempenhoAnual()
    {
        $empresaId = Auth::user()->empresa_id;

        // Buscar vendedores da empresa
        $vendedores = User::where('empresa_id', $empresaId)
            ->select('id', 'name')
            ->where('user_role_id', 1)
            ->where('ativo', 'Y')
            ->orderBy('name')
            ->get();

        // Buscar anos disponíveis
        $anos = VendasModel::whereHas('user', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })
            ->select(DB::raw('YEAR(created_at) as ano'))
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano');

        return view('content.pages.relatorios.desempenho-anual', [
            'vendedores' => $vendedores,
            'anos' => $anos,
        ]);
    }

    public function desempenhoAnualData(Request $request)
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $ano = $request->get('ano', date('Y'));
            $vendedorId = $request->get('vendedor_id');

            // Definir intervalo do ano
            $dataInicio = Carbon::create($ano, 1, 1)->startOfDay();
            $dataFim = Carbon::create($ano, 12, 31)->endOfDay();

            // 1. Estatísticas gerais do ano
            $estatisticasGerais = $this->getEstatisticasGeraisAno($empresaId, $ano, $vendedorId);

            // 2. Dados por trimestre
            $dadosTrimestre = $this->getDadosPorTrimestre($empresaId, $ano, $vendedorId);

            // 3. Evolução mensal
            $evolucaoMensal = $this->getEvolucaoMensal($empresaId, $ano, $vendedorId);

            // 4. Top planos mais vendidos
            $topPlanos = $this->getTopPlanosVendidos($empresaId, $ano, $vendedorId);

            // 5. Distribuição por operadora
            $distribuicaoOperadora = $this->getDistribuicaoPorOperadora($empresaId, $ano, $vendedorId);

            // 6. Taxa de conversão (leads trabalhados vs vendas)
            $taxaConversao = $this->getTaxaConversao($empresaId, $ano, $vendedorId);

            // 7. Ranking de vendedores (se não filtrou por vendedor específico)
            $rankingVendedores = null;
            if (! $vendedorId) {

                $rankingVendedores = $this->getRankingVendedoresAno($empresaId, $ano);
            }

            // 8. Detalhes do vendedor (se filtrou)
            $detalhesVendedor = null;
            if ($vendedorId) {
                $detalhesVendedor = $this->getDetalhesVendedor($empresaId, $ano, $vendedorId);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'estatisticas_gerais' => $estatisticasGerais,
                    'dados_trimestre' => $dadosTrimestre,
                    'evolucao_mensal' => $evolucaoMensal,
                    'top_planos' => $topPlanos,
                    'distribuicao_operadora' => $distribuicaoOperadora,
                    'taxa_conversao' => $taxaConversao,
                    'ranking_vendedores' => $rankingVendedores,
                    'detalhes_vendedor' => $detalhesVendedor,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getEstatisticasGeraisAno($empresaId, $ano, $vendedorId = null)
    {
        // Leads únicos trabalhados no ano
        $queryLeads = DB::table('lead_atividades as la')
            ->join('contatos as c', 'c.id', '=', 'la.contato_id')
            ->where('la.empresa_id', $empresaId)
            ->whereYear('la.created_at', $ano);

        if ($vendedorId) {
            $queryLeads->where('la.user_id', $vendedorId);
        }

        $totalLeadsUnicos = $queryLeads->distinct('la.contato_id')->count('la.contato_id');

        // Vendas realizadas
        $queryVendas = VendasModel::whereHas('user', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })
            ->whereHas('contatoCorretor', function ($q) {
                $q->whereIn('tabulacao_id', [
                    Tabulations::VENDA,
                    Tabulations::IMPLANTADO,
                    Tabulations::ESTORNO,
                    Tabulations::PENDENCIA,
                    Tabulations::ANALISE_OPERADORA,
                    Tabulations::BOLETO_DISPONIVEL,
                    Tabulations::REGULARIZADO,
                    Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                    Tabulations::ANALISE_DOCUMENTOS,
                    Tabulations::AGUARD_ASSINATURA_DS,
                ]);
            })
            ->whereYear('vendas.created_at', $ano);

        if ($vendedorId) {
            $queryVendas->where('user_id', $vendedorId);
        }

        $totalVendas = $queryVendas->count();
        $valorTotal = (clone $queryVendas)->selectRaw('SUM(valor_contrato + CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END) as total')->value('total') ?? 0;
        $ticketMedio = $totalVendas > 0 ? $valorTotal / $totalVendas : 0;

        // Taxa de conversão
        $taxaConversao = $totalLeadsUnicos > 0 ? round(($totalVendas / $totalLeadsUnicos) * 100, 2) : 0;

        return [
            'total_leads_unicos' => $totalLeadsUnicos,
            'total_vendas' => $totalVendas,
            'valor_total' => $valorTotal,
            'ticket_medio' => $ticketMedio,
            'taxa_conversao' => $taxaConversao,
        ];
    }

    private function getDadosPorTrimestre($empresaId, $ano, $vendedorId = null)
    {
        $trimestres = [];

        for ($trimestre = 1; $trimestre <= 4; $trimestre++) {
            $mesInicio = ($trimestre - 1) * 3 + 1;
            $mesFim = $trimestre * 3;

            // Leads únicos do trimestre
            $queryLeads = DB::table('lead_atividades as la')
                ->where('la.empresa_id', $empresaId)
                ->whereYear('la.created_at', $ano)
                ->whereRaw('MONTH(la.created_at) BETWEEN ? AND ?', [$mesInicio, $mesFim]);

            if ($vendedorId) {
                $queryLeads->where('la.user_id', $vendedorId);
            }

            $leadsUnicos = $queryLeads->distinct('la.contato_id')->count('la.contato_id');

            // Vendas do trimestre
            $queryVendas = VendasModel::whereHas('user', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
                ->whereHas('contatoCorretor', function ($q) {
                    $q->whereIn('tabulacao_id', [
                        Tabulations::VENDA,
                        Tabulations::IMPLANTADO,
                        Tabulations::ESTORNO,
                        Tabulations::PENDENCIA,
                        Tabulations::ANALISE_OPERADORA,
                        Tabulations::BOLETO_DISPONIVEL,
                        Tabulations::REGULARIZADO,
                        Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                        Tabulations::ANALISE_DOCUMENTOS,
                        Tabulations::AGUARD_ASSINATURA_DS,
                    ]);
                })
                ->whereYear('vendas.created_at', $ano)
                ->whereRaw('MONTH(vendas.created_at) BETWEEN ? AND ?', [$mesInicio, $mesFim]);

            if ($vendedorId) {
                $queryVendas->where('user_id', $vendedorId);
            }

            $totalVendas = $queryVendas->count();
            $valorTotal = (clone $queryVendas)->selectRaw('SUM(valor_contrato + CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END) as total')->value('total') ?? 0;
            $valorAngariacao = (clone $queryVendas)->selectRaw('SUM(CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END) as total')->value('total') ?? 0;
            $ticketMedio = $totalVendas > 0 ? $valorTotal / $totalVendas : 0;
            $taxaConversao = $leadsUnicos > 0 ? round(($totalVendas / $leadsUnicos) * 100, 2) : 0;

            $trimestres[] = [
                'trimestre' => $trimestre,
                'periodo' => "Q{$trimestre} ({$ano})",
                'leads_unicos' => $leadsUnicos,
                'total_vendas' => $totalVendas,
                'valor_total' => $valorTotal,
                'valor_angariacao' => $valorAngariacao,
                'ticket_medio' => $ticketMedio,
                'taxa_conversao' => $taxaConversao,
            ];
        }

        return $trimestres;
    }

    private function getEvolucaoMensal($empresaId, $ano, $vendedorId = null)
    {
        $meses = [];
        $nomesMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        for ($mes = 1; $mes <= 12; $mes++) {
            // Leads únicos do mês
            $queryLeads = DB::table('lead_atividades as la')
                ->where('la.empresa_id', $empresaId)
                ->whereYear('la.created_at', $ano)
                ->whereMonth('la.created_at', $mes);

            if ($vendedorId) {
                $queryLeads->where('la.user_id', $vendedorId);
            }

            $leadsUnicos = $queryLeads->distinct('la.contato_id')->count('la.contato_id');

            // Vendas do mês
            $queryVendas = VendasModel::whereHas('user', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
                ->whereHas('contatoCorretor', function ($q) {
                    $q->whereIn('tabulacao_id', [
                        Tabulations::VENDA,
                        Tabulations::IMPLANTADO,
                        Tabulations::ESTORNO,
                        Tabulations::PENDENCIA,
                        Tabulations::ANALISE_OPERADORA,
                        Tabulations::BOLETO_DISPONIVEL,
                        Tabulations::REGULARIZADO,
                        Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                        Tabulations::ANALISE_DOCUMENTOS,
                        Tabulations::AGUARD_ASSINATURA_DS,
                    ]);
                })
                ->whereYear('vendas.created_at', $ano)
                ->whereMonth('vendas.created_at', $mes);

            if ($vendedorId) {
                $queryVendas->where('user_id', $vendedorId);
            }

            $totalVendas = $queryVendas->count();
            $valorTotal = (clone $queryVendas)->selectRaw('SUM(valor_contrato + CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END) as total')->value('total') ?? 0;

            $meses[] = [
                'mes' => $mes,
                'nome_mes' => $nomesMeses[$mes - 1],
                'leads_unicos' => $leadsUnicos,
                'total_vendas' => $totalVendas,
                'valor_total' => $valorTotal,
            ];
        }

        return $meses;
    }

    private function getTopPlanosVendidos($empresaId, $ano, $vendedorId = null)
    {
        $query = VendasModel::select(
            'nome_plano',
            'operadora',
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw('SUM(valor_contrato + CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END) as valor_total')
        )
            ->whereHas('user', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->whereHas('contatoCorretor', function ($q) {
                $q->whereIn('tabulacao_id', [
                    Tabulations::VENDA,
                    Tabulations::IMPLANTADO,
                    Tabulations::ESTORNO,
                    Tabulations::PENDENCIA,
                    Tabulations::ANALISE_OPERADORA,
                    Tabulations::BOLETO_DISPONIVEL,
                    Tabulations::REGULARIZADO,
                    Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                    Tabulations::ANALISE_DOCUMENTOS,
                    Tabulations::AGUARD_ASSINATURA_DS,
                ]);
            })
            ->whereYear('vendas.created_at', $ano);

        if ($vendedorId) {
            $query->where('user_id', $vendedorId);
        }

        return $query->whereNotNull('nome_plano')
            ->groupBy('nome_plano', 'operadora')
            ->orderBy('total_vendas', 'desc')
            ->limit(10)
            ->get();
    }

    private function getDistribuicaoPorOperadora($empresaId, $ano, $vendedorId = null)
    {
        $query = VendasModel::select(
            'operadora',
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw('SUM(valor_contrato + CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END) as valor_total'),
            DB::raw('AVG(valor_contrato + CASE WHEN angariacao_status = "SIM" THEN angariacao_valor ELSE 0 END) as ticket_medio')
        )
            ->whereHas('user', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->whereHas('contatoCorretor', function ($q) {
                $q->whereIn('tabulacao_id', [
                    Tabulations::VENDA,
                    Tabulations::IMPLANTADO,
                    Tabulations::ESTORNO,
                    Tabulations::PENDENCIA,
                    Tabulations::ANALISE_OPERADORA,
                    Tabulations::BOLETO_DISPONIVEL,
                    Tabulations::REGULARIZADO,
                    Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                    Tabulations::ANALISE_DOCUMENTOS,
                    Tabulations::AGUARD_ASSINATURA_DS,
                ]);
            })
            ->whereYear('vendas.created_at', $ano);

        if ($vendedorId) {
            $query->where('user_id', $vendedorId);
        }

        return $query->whereNotNull('operadora')
            ->groupBy('operadora')
            ->orderBy('valor_total', 'desc')
            ->get();
    }

    private function getTaxaConversao($empresaId, $ano, $vendedorId = null)
    {
        $taxasPorMes = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $queryLeads = DB::table('lead_atividades as la')
                ->where('la.empresa_id', $empresaId)
                ->whereYear('la.created_at', $ano)
                ->whereMonth('la.created_at', $mes);

            if ($vendedorId) {
                $queryLeads->where('la.user_id', $vendedorId);
            }

            $leadsUnicos = $queryLeads->distinct('la.contato_id')->count('la.contato_id');

            $queryVendas = VendasModel::whereHas('user', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
                ->whereHas('contatoCorretor', function ($q) {
                    $q->whereIn('tabulacao_id', [
                        Tabulations::VENDA,
                        Tabulations::IMPLANTADO,
                        Tabulations::ESTORNO,
                        Tabulations::PENDENCIA,
                        Tabulations::ANALISE_OPERADORA,
                        Tabulations::BOLETO_DISPONIVEL,
                        Tabulations::REGULARIZADO,
                        Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                        Tabulations::ANALISE_DOCUMENTOS,
                        Tabulations::AGUARD_ASSINATURA_DS,
                    ]);
                })
                ->whereYear('vendas.created_at', $ano)
                ->whereMonth('vendas.created_at', $mes);

            if ($vendedorId) {
                $queryVendas->where('user_id', $vendedorId);
            }

            $totalVendas = $queryVendas->count();
            $taxa = $leadsUnicos > 0 ? round(($totalVendas / $leadsUnicos) * 100, 2) : 0;

            $taxasPorMes[] = [
                'mes' => $mes,
                'leads' => $leadsUnicos,
                'vendas' => $totalVendas,
                'taxa' => $taxa,
            ];
        }

        return $taxasPorMes;
    }

    private function getRankingVendedoresAno($empresaId, $ano)
    {
        // Subquery para leads únicos por vendedor
        $leadsSubquery = DB::table('lead_atividades as la')
            ->select('user_id', DB::raw('COUNT(DISTINCT contato_id) as total_leads'))
            ->whereYear('created_at', $ano)
            ->where('empresa_id', $empresaId)
            ->groupBy('user_id');

        // Subquery para vendas por vendedor
        $vendasSubquery = DB::table('vendas as v')
            ->select(
                'v.user_id',
                DB::raw('COUNT(DISTINCT v.id) as total_vendas'),
                DB::raw('SUM(v.valor_contrato + CASE WHEN v.angariacao_status = "SIM" THEN v.angariacao_valor ELSE 0 END) as valor_total'),
                DB::raw('AVG(v.valor_contrato + CASE WHEN v.angariacao_status = "SIM" THEN v.angariacao_valor ELSE 0 END) as ticket_medio')
            )
            ->where('v.empresa_id', $empresaId)
            ->whereYear('v.created_at', $ano)
            ->whereIn('v.tabulacao_id', [
                Tabulations::VENDA,
                Tabulations::IMPLANTADO,
                Tabulations::ESTORNO,
                Tabulations::PENDENCIA,
                Tabulations::ANALISE_OPERADORA,
                Tabulations::BOLETO_DISPONIVEL,
                Tabulations::REGULARIZADO,
                Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                Tabulations::ANALISE_DOCUMENTOS,
                Tabulations::AGUARD_ASSINATURA_DS,
            ])
            ->groupBy('v.user_id');

        return DB::table('users as u')
            ->leftJoinSub($leadsSubquery, 'leads', function ($join) {
                $join->on('leads.user_id', '=', 'u.id');
            })
            ->leftJoinSub($vendasSubquery, 'vendas', function ($join) {
                $join->on('vendas.user_id', '=', 'u.id');
            })
            ->where('u.empresa_id', $empresaId)
            ->where('u.ativo', 'Y')
            ->where('u.user_role_id', 1)
            ->select(
                'u.id',
                'u.name as vendedor',
                DB::raw('COALESCE(leads.total_leads, 0) as total_leads'),
                DB::raw('COALESCE(vendas.total_vendas, 0) as total_vendas'),
                DB::raw('COALESCE(vendas.valor_total, 0) as valor_total'),
                DB::raw('COALESCE(vendas.ticket_medio, 0) as ticket_medio'),
                DB::raw('CASE WHEN COALESCE(leads.total_leads, 0) > 0 THEN ROUND((COALESCE(vendas.total_vendas, 0) / leads.total_leads) * 100, 2) ELSE 0 END as taxa_conversao')
            )
            ->orderBy('valor_total', 'desc')
            ->get();
    }

    private function getDetalhesVendedor($empresaId, $ano, $vendedorId)
    {
        $vendedor = User::find($vendedorId);

        if (! $vendedor) {
            return null;
        }

        // Planos mais vendidos pelo vendedor
        $planosVendedor = VendasModel::select(
            'nome_plano',
            'operadora',
            DB::raw('COUNT(*) as total')
        )
            ->where('user_id', $vendedorId)
            ->whereYear('created_at', $ano)
            ->whereNotNull('nome_plano')
            ->groupBy('nome_plano', 'operadora')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        return [
            'nome' => $vendedor->name,
            'planos_favoritos' => $planosVendedor,
        ];
    }

    /**
     * Relatório de Distribuição de Leads
     */
    public function distribuicaoLeads()
    {
        return view('content.pages.relatorios.distribuicao-leads', [
            'inicioMes' => now()->startOfMonth()->format('Y-m-d'),
            'hoje' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Dados do Relatório de Distribuição de Leads
     */
    public function distribuicaoLeadsData(Request $request)
    {
        $request->validate([
            'data_inicial' => 'nullable|date_format:Y-m-d|required_with:data_final',
            'data_final' => 'nullable|date_format:Y-m-d|required_with:data_inicial|after_or_equal:data_inicial',
        ]);

        $empresaId = Auth::user()->empresa_id;
        $dataInicial = $request->filled('data_inicial') ? Carbon::parse($request->input('data_inicial'))->startOfDay() : null;
        $dataFinal = $request->filled('data_final') ? Carbon::parse($request->input('data_final'))->endOfDay() : null;

        $aplicarPeriodoDoLead = static function ($query, string $coluna = 'contatos.created_at') use ($dataInicial, $dataFinal): void {
            if ($dataInicial && $dataFinal) {
                $query->whereBetween($coluna, [$dataInicial, $dataFinal]);
            }
        };
        $tabulacoesVendaValida = $this->tabulacoesVendaValida();
        $tabulacoesFilaImplantacao = array_values(array_diff(
            $tabulacoesVendaValida,
            [Tabulations::IMPLANTADO]
        ));

        // O filtro representa uma coorte: em todos os blocos abaixo, o período é
        // aplicado à data de entrada do lead, e não à data da movimentação.
        $totalLeadsQuery = DB::table('contatos')
            ->where('empresa_id', $empresaId);
        $aplicarPeriodoDoLead($totalLeadsQuery, 'created_at');
        $totalLeads = $totalLeadsQuery->count();

        // Leads que já tiveram atribuição. Remarketing continua sendo uma atribuição,
        // mas aparece separado de "em trabalho com vendedores" abaixo.
        $leadsDistribuidosQuery = DB::table('contatos_corretores')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->where('contatos_corretores.empresa_id', $empresaId);
        $aplicarPeriodoDoLead($leadsDistribuidosQuery);
        $leadsDistribuidos = $leadsDistribuidosQuery->distinct()->count('contatos_corretores.contato_id');

        // Em trabalho comercial: lead ativo, com vendedor e em tabulação comercial.
        // Remarketing não é trabalho ativo e, por isso, não entra neste número.
        $leadsComercialQuery = DB::table('contatos_corretores')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where('contatos.status', 'Y')
            ->where('tabulacoes.tipo_tabulacao', 'C')
            ->where('tabulacoes.id', '!=', Tabulations::REMARKETING);
        $aplicarPeriodoDoLead($leadsComercialQuery);
        $leadsComercial = $leadsComercialQuery->distinct()->count('contatos_corretores.contato_id');

        // Preditiva: apenas a fila atual. Registros inativos são históricos e um mesmo
        // lead é contado uma única vez, ainda que tenha retornado à fila.
        $leadsPreditivaQuery = DB::table('preditiva')
            ->join('contatos', 'preditiva.contato_id', '=', 'contatos.id')
            ->where('preditiva.empresa_id', $empresaId)
            ->where('preditiva.status', 'Y');
        $aplicarPeriodoDoLead($leadsPreditivaQuery);
        $leadsPreditiva = $leadsPreditivaQuery->distinct()->count('preditiva.contato_id');

        $leadsRemarketingQuery = DB::table('contatos_corretores')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where('contatos.status', 'Y')
            ->where('contatos_corretores.tabulacao_id', Tabulations::REMARKETING);
        $aplicarPeriodoDoLead($leadsRemarketingQuery);
        $leadsRemarketing = $leadsRemarketingQuery->distinct()->count('contatos_corretores.contato_id');

        // O reservatório é a fonte de verdade dos leads novos prontos para envio
        // que ainda não foram distribuídos. As salvaguardas abaixo evitam contar
        // itens disponíveis que tenham ficado obsoletos antes da sincronização da fila.
        $leadsReservatorioQuery = DB::table('lead_reservatorio_itens as reservatorio')
            ->join('contatos', 'contatos.id', '=', 'reservatorio.contato_id')
            ->where('reservatorio.empresa_id', $empresaId)
            ->where('reservatorio.status', LeadReservatorioItem::STATUS_DISPONIVEL)
            ->where('contatos.empresa_id', $empresaId)
            ->where('contatos.status', 'Y')
            ->whereNotExists(function ($query) use ($empresaId) {
                $query->selectRaw('1')
                    ->from('contatos_corretores')
                    ->whereColumn('contatos_corretores.contato_id', 'contatos.id')
                    ->where('contatos_corretores.empresa_id', $empresaId);
            })
            ->whereNotExists(function ($query) use ($empresaId) {
                $query->selectRaw('1')
                    ->from('preditiva')
                    ->whereColumn('preditiva.contato_id', 'contatos.id')
                    ->where('preditiva.empresa_id', $empresaId)
                    ->where('preditiva.status', 'Y');
            })
            ->whereNotExists(function ($query) use ($empresaId, $tabulacoesVendaValida) {
                $query->selectRaw('1')
                    ->from('vendas')
                    ->whereColumn('vendas.contato_id', 'contatos.id')
                    ->where('vendas.empresa_id', $empresaId)
                    ->whereIn('vendas.tabulacao_id', $tabulacoesVendaValida);
            });
        $aplicarPeriodoDoLead($leadsReservatorioQuery, 'contatos.created_at');
        $leadsReservatorio = $leadsReservatorioQuery->distinct()->count('reservatorio.contato_id');

        $contarLeadsPorStatusDaVenda = function (array $tabulacoes) use ($empresaId, $aplicarPeriodoDoLead): int {
            $query = DB::table('vendas')
                ->join('contatos', 'contatos.id', '=', 'vendas.contato_id')
                ->where('vendas.empresa_id', $empresaId)
                ->whereIn('vendas.tabulacao_id', $tabulacoes);
            $aplicarPeriodoDoLead($query);

            return $query->distinct()->count('vendas.contato_id');
        };

        // O status da venda é a fonte de verdade do pós-venda. Administrativo é
        // somente a fila em implantação; implantado, declinado e estornado são saídas.
        $leadsAdministrativo = $contarLeadsPorStatusDaVenda($tabulacoesFilaImplantacao);
        $leadsViraramVenda = $contarLeadsPorStatusDaVenda($tabulacoesVendaValida);
        $leadsCarteiraClientes = $contarLeadsPorStatusDaVenda([Tabulations::IMPLANTADO]);
        $leadsDeclinados = $contarLeadsPorStatusDaVenda([Tabulations::DECLINIO]);
        $leadsEstornados = $contarLeadsPorStatusDaVenda([Tabulations::ESTORNO]);

        // Leads descartados (status = 'N')
        $leadsDescartadosQuery = DB::table('contatos')
            ->where('empresa_id', $empresaId)
            ->where('status', 'N');

        $aplicarPeriodoDoLead($leadsDescartadosQuery, 'created_at');
        $leadsDescartados = $leadsDescartadosQuery->count();

        // Distribuição por status (tabulações) - Todos
        $distribuicaoPorStatus = DB::table('contatos_corretores')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->select('tabulacoes.descricao', DB::raw('COUNT(DISTINCT contatos_corretores.contato_id) as total'))
            ->where('contatos_corretores.empresa_id', $empresaId);

        $aplicarPeriodoDoLead($distribuicaoPorStatus);

        $distribuicaoPorStatus = $distribuicaoPorStatus
            ->groupBy('tabulacoes.descricao')
            ->orderBy('total', 'desc')
            ->get();

        // Distribuição comercial: todos os status comerciais atuais, exceto
        // remarketing, sem depender de uma lista fixa de nomes.
        $distribuicaoComercial = DB::table('contatos_corretores')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->select('tabulacoes.descricao', DB::raw('COUNT(DISTINCT contatos_corretores.contato_id) as total'))
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where('contatos.status', 'Y')
            ->where('tabulacoes.tipo_tabulacao', 'C')
            ->where('tabulacoes.id', '!=', Tabulations::REMARKETING);
        $aplicarPeriodoDoLead($distribuicaoComercial);

        $distribuicaoComercial = $distribuicaoComercial
            ->groupBy('tabulacoes.descricao')
            ->orderBy('total', 'desc')
            ->get();

        $distribuicaoAdministrativa = DB::table('vendas')
            ->join('tabulacoes', 'vendas.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'vendas.contato_id', '=', 'contatos.id')
            ->select('tabulacoes.descricao', DB::raw('COUNT(DISTINCT vendas.contato_id) as total'))
            ->where('vendas.empresa_id', $empresaId)
            ->whereIn('vendas.tabulacao_id', $tabulacoesFilaImplantacao);
        $aplicarPeriodoDoLead($distribuicaoAdministrativa);

        $distribuicaoAdministrativa = $distribuicaoAdministrativa
            ->groupBy('tabulacoes.descricao')
            ->orderBy('total', 'desc')
            ->get();

        // Distribuição de Descarte - sub_tabulacao = 'Y' + REMARKETING
        $distribuicaoDescarte = DB::table('contatos_corretores')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->select('tabulacoes.descricao', DB::raw('COUNT(DISTINCT contatos_corretores.contato_id) as total'))
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where(function ($query) {
                $query->where('tabulacoes.sub_tabulacao', 'Y')
                    ->orWhere('tabulacoes.descricao', 'REMARKETING');
            });

        $aplicarPeriodoDoLead($distribuicaoDescarte);

        $distribuicaoDescarte = $distribuicaoDescarte
            ->groupBy('tabulacoes.descricao')
            ->orderBy('total', 'desc')
            ->get();

        // Motivos de Descarte - distribuição por subtabulação dos leads descartados
        $motivosDescarte = DB::table('contatos_corretores')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->leftJoin('tabulacoes as sub_tab', 'contatos_corretores.sub_tabulacao_id', '=', 'sub_tab.id')
            ->select(
                DB::raw('CASE
                    WHEN sub_tab.descricao IS NOT NULL THEN sub_tab.descricao
                    WHEN tabulacoes.descricao = "REMARKETING" THEN "REMARKETING"
                    WHEN tabulacoes.sub_tabulacao = "Y" THEN "Sem motivo especificado"
                    ELSE "Sem motivo"
                END as motivo'),
                DB::raw('COUNT(DISTINCT contatos_corretores.contato_id) as total')
            )
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where(function ($query) {
                $query->where('tabulacoes.sub_tabulacao', 'Y')
                    ->orWhere('tabulacoes.descricao', 'REMARKETING');
            });

        $aplicarPeriodoDoLead($motivosDescarte);

        $motivosDescarte = $motivosDescarte
            ->groupBy('motivo')
            ->orderBy('total', 'desc')
            ->get();

        // Tentativas de contato na preditiva
        $tentativasPreditiva = DB::table('log_preditiva')
            ->where('empresa_id', $empresaId);

        if ($dataInicial && $dataFinal) {
            $tentativasPreditiva->whereBetween('created_at', [$dataInicial, $dataFinal]);
        }

        $tentativasPreditiva = $tentativasPreditiva->count();

        // Evolução de entrada e distribuição. Períodos longos são agrupados por mês
        // para manter o gráfico legível.
        $agruparPorMes = $dataInicial && $dataFinal && $dataInicial->diffInDays($dataFinal) > 62;
        $expressaoPeriodo = $agruparPorMes
            ? 'DATE_FORMAT(contatos.created_at, "%Y-%m")'
            : 'DATE(contatos.created_at)';
        $evolucao = DB::table('contatos')
            ->leftJoin('contatos_corretores', function ($join) use ($empresaId) {
                $join->on('contatos_corretores.contato_id', '=', 'contatos.id')
                    ->where('contatos_corretores.empresa_id', $empresaId);
            })
            ->where('contatos.empresa_id', $empresaId)
            ->when($dataInicial && $dataFinal, fn ($query) => $query->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]))
            ->selectRaw("{$expressaoPeriodo} as periodo")
            ->selectRaw('COUNT(DISTINCT contatos.id) as total')
            ->selectRaw('COUNT(DISTINCT contatos_corretores.contato_id) as distribuidos')
            ->groupByRaw($expressaoPeriodo)
            ->orderBy('periodo')
            ->get();

        // Ranking operacional: a coluna administrativa também usa vendas, para ficar
        // coerente com a fila de implantação exibida no restante do relatório.
        $rankingVendedores = DB::table('contatos_corretores')
            ->join('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
            ->join('users', 'users.id', '=', 'contatos_corretores.user_id')
            ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->when($dataInicial && $dataFinal, fn ($query) => $query->whereBetween('contatos_corretores.created_at', [$dataInicial, $dataFinal]))
            ->select('users.id', 'users.name')
            ->selectRaw('COUNT(DISTINCT contatos_corretores.contato_id) as total')
            ->selectRaw("COUNT(DISTINCT CASE WHEN contatos.status = 'Y' AND tabulacoes.tipo_tabulacao = 'C' AND tabulacoes.id <> ? THEN contatos_corretores.contato_id END) as comercial", [Tabulations::REMARKETING])
            ->selectRaw('COUNT(DISTINCT CASE WHEN tabulacoes.id = ? THEN contatos_corretores.contato_id END) as remarketing', [Tabulations::REMARKETING])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $totalAtribuidosVendedoresQuery = DB::table('contatos_corretores')
            ->where('empresa_id', $empresaId)
            ->when($dataInicial && $dataFinal, fn ($query) => $query->whereBetween('created_at', [$dataInicial, $dataFinal]));
        $totalAtribuidosVendedores = $totalAtribuidosVendedoresQuery->distinct()->count('contato_id');

        $administrativoPorVendedor = DB::table('vendas')
            ->join('contatos', 'contatos.id', '=', 'vendas.contato_id')
            ->where('vendas.empresa_id', $empresaId)
            ->whereIn('vendas.tabulacao_id', $tabulacoesFilaImplantacao)
            ->when($dataInicial && $dataFinal, fn ($query) => $query->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]))
            ->select('vendas.user_id')
            ->selectRaw('COUNT(DISTINCT vendas.contato_id) as total')
            ->groupBy('vendas.user_id')
            ->pluck('total', 'vendas.user_id');
        $rankingVendedores->each(function ($vendedor) use ($administrativoPorVendedor): void {
            $vendedor->administrativo = (int) ($administrativoPorVendedor[$vendedor->id] ?? 0);
        });

        $leadsNaoDistribuidos = $leadsReservatorio;
        $baseDistribuicao = $leadsDistribuidos + $leadsReservatorio;
        $percentual = static fn (int $valor, int $base): float => $base > 0 ? round(($valor / $base) * 100, 1) : 0.0;

        return response()->json([
            'resumo' => [
                'total_leads' => $totalLeads,
                'leads_distribuidos' => $leadsDistribuidos,
                'leads_comercial' => $leadsComercial,
                'leads_administrativo' => $leadsAdministrativo,
                'leads_fila_implantacao' => $leadsAdministrativo,
                'leads_preditiva' => $leadsPreditiva,
                'leads_remarketing' => $leadsRemarketing,
                'leads_reservatorio' => $leadsReservatorio,
                // Alias temporário para consumidores antigos deste endpoint.
                'leads_sem_atribuicao' => $leadsReservatorio,
                'leads_viraram_venda' => $leadsViraramVenda,
                'leads_carteira_clientes' => $leadsCarteiraClientes,
                'leads_declinados' => $leadsDeclinados,
                'leads_estornados' => $leadsEstornados,
                'leads_descartados' => $leadsDescartados,
                'tentativas_preditiva' => $tentativasPreditiva,
                'leads_nao_distribuidos' => $leadsNaoDistribuidos,
                'leads_atribuidos_vendedores' => $totalAtribuidosVendedores,
                'cobertura_distribuicao' => $percentual($leadsDistribuidos, $baseDistribuicao),
                'taxa_comercial' => $percentual($leadsComercial, $leadsDistribuidos),
                'taxa_administrativo' => $percentual($leadsAdministrativo, $leadsViraramVenda),
                'taxa_venda' => $percentual($leadsViraramVenda, $totalLeads),
                'taxa_implantacao' => $percentual($leadsCarteiraClientes, $leadsViraramVenda),
                'taxa_descarte' => $percentual($leadsDescartados, $totalLeads),
                'tentativas_por_lead' => $leadsPreditiva > 0 ? round($tentativasPreditiva / $leadsPreditiva, 1) : 0.0,
            ],
            'periodo' => [
                'inicio' => $dataInicial?->format('d/m/Y'),
                'fim' => $dataFinal?->format('d/m/Y'),
                'agrupamento' => $agruparPorMes ? 'mensal' : 'diario',
            ],
            'distribuicao_status' => $distribuicaoPorStatus,
            'distribuicao_comercial' => $distribuicaoComercial,
            'distribuicao_administrativa' => $distribuicaoAdministrativa,
            'distribuicao_descarte' => $distribuicaoDescarte,
            'motivos_descarte' => $motivosDescarte,
            'evolucao' => $evolucao,
            'ranking_vendedores' => $rankingVendedores,
        ]);
    }

    /**
     * Detalha a fila comercial e as vendas de um vendedor na coorte do relatório.
     */
    public function distribuicaoLeadsVendedorDetalhes(Request $request, int $vendedor)
    {
        $request->validate([
            'data_inicial' => 'nullable|date_format:Y-m-d|required_with:data_final',
            'data_final' => 'nullable|date_format:Y-m-d|required_with:data_inicial|after_or_equal:data_inicial',
        ]);

        $empresaId = (int) Auth::user()->empresa_id;
        $dataInicial = $request->filled('data_inicial') ? Carbon::parse($request->input('data_inicial'))->startOfDay() : null;
        $dataFinal = $request->filled('data_final') ? Carbon::parse($request->input('data_final'))->endOfDay() : null;

        $usuario = User::query()
            ->where('empresa_id', $empresaId)
            ->findOrFail($vendedor);

        $aplicarPeriodoDaDistribuicao = static function ($query) use ($dataInicial, $dataFinal): void {
            if ($dataInicial && $dataFinal) {
                $query->whereBetween('contatos_corretores.created_at', [$dataInicial, $dataFinal]);
            }
        };

        $filaComercialQuery = DB::table('contatos_corretores')
            ->join('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
            ->join('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where('contatos_corretores.user_id', $usuario->id)
            ->where('contatos.empresa_id', $empresaId)
            ->where('contatos.status', 'Y')
            ->where('tabulacoes.tipo_tabulacao', 'C')
            ->where('tabulacoes.id', '!=', Tabulations::REMARKETING)
            ->select('tabulacoes.descricao')
            ->selectRaw('COUNT(DISTINCT contatos_corretores.contato_id) as total');
        $aplicarPeriodoDaDistribuicao($filaComercialQuery);

        $filaComercial = $filaComercialQuery
            ->groupBy('tabulacoes.descricao')
            ->orderByDesc('total')
            ->orderBy('tabulacoes.descricao')
            ->get();

        $viraramVendaQuery = DB::table('vendas')
            ->join('contatos', 'contatos.id', '=', 'vendas.contato_id')
            ->where('vendas.empresa_id', $empresaId)
            ->where('vendas.user_id', $usuario->id)
            ->where('contatos.empresa_id', $empresaId)
            ->whereIn('vendas.tabulacao_id', $this->tabulacoesVendaValida())
            ->when($dataInicial && $dataFinal, fn ($query) => $query->whereBetween('vendas.created_at', [$dataInicial, $dataFinal]))
            ->whereExists(function ($query) use ($empresaId, $dataInicial, $dataFinal) {
                $query->selectRaw('1')
                    ->from('contatos_corretores as atribuicao')
                    ->whereColumn('atribuicao.contato_id', 'vendas.contato_id')
                    ->whereColumn('atribuicao.user_id', 'vendas.user_id')
                    ->where('atribuicao.empresa_id', $empresaId)
                    ->when($dataInicial && $dataFinal, fn ($subquery) => $subquery->whereBetween('atribuicao.created_at', [$dataInicial, $dataFinal]));
            });

        return response()->json([
            'vendedor' => [
                'id' => $usuario->id,
                'nome' => $usuario->name,
            ],
            'periodo' => [
                'inicio' => $dataInicial?->format('d/m/Y'),
                'fim' => $dataFinal?->format('d/m/Y'),
            ],
            'fila_comercial' => $filaComercial,
            'total_fila_comercial' => (int) $filaComercial->sum('total'),
            'viraram_venda' => $viraramVendaQuery->distinct()->count('vendas.contato_id'),
        ]);
    }
}
