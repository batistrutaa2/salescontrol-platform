<?php

namespace App\Http\Controllers\pages\relatorios;

use App\Models\LogPreditiva;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\LigacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Models\Vendas as VendasModel;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\Tabulations;

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
            'users' => $user
        ]);
    }


    public function getLigacoes($id_user, $data_inicial, $data_final)
    {
        $ligacoes = $this->ligacoesRepository->getLigacoes($id_user, $data_inicial, $data_final);
        $filaAtual = $this->contatosCorretoresRepository->getQueueCurrent($id_user);

        return response()->json([
            'ligacoes' => $ligacoes,
            'fila' => $filaAtual
        ]);
    }


    public function predictiveReport()
    {
        $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
        return view('content.pages.relatorios.preditiva', [
            'usuarios' => $users
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
                'observacao' => '' // Não há campo de observação no log_preditiva, então deixamos vazio
            ];
        });

        // Calcular resumo
        $total = $logs->count();
        $convertidos = $logs->where('acao', 'CONVERSAO')->count();
        $cotacoes = $logs->where('tabulacao', 'COTACAO')->count();
        $ligarDepois = $logs->where('tabulacao', 'LIGAR_DEPOIS')->count();
        $descartados = $logs->where('acao', 'DESCARTE')->count();
        $taxaConversao = $total > 0 ? round(($convertidos / $total) * 100, 1) . '%' : '0%';

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
            })->toArray()
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
            'nomes' => array_map(fn($i) => $vendedorNomes[$i], $indices),
            'convertidos' => array_map(fn($i) => $vendedorConvertidos[$i], $indices),
            'cotacoes' => array_map(fn($i) => $vendedorCotacoes[$i], $indices),
            'ligar_depois' => array_map(fn($i) => $vendedorLigarDepois[$i], $indices),
            'descartados' => array_map(fn($i) => $vendedorDescartados[$i], $indices),
            'total' => array_map(fn($i) => $vendedorTotal[$i], $indices),
            'taxa' => array_map(fn($i) => $vendedorTaxa[$i], $indices),
        ];

        // Adicionar dados de tabulação
        $tabulacaoCount = $logs->groupBy('tabulacao')->map->count();
        $dadosGraficoTabulacao = [
            'labels' => $tabulacaoCount->keys()->toArray(),
            'valores' => $tabulacaoCount->values()->toArray()
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
                'taxa_conversao' => $taxaConversao
            ],
            'grafico_diario' => $dadosGraficoDiario,
            'grafico_vendedores' => $dadosGraficoVendedores,
            'grafico_tabulacao' => $dadosGraficoTabulacao
        ]);
    }

    public function activityReport()
    {
        $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);

        return view('content.pages.relatorios.atividades', [
            'users' => $users
        ]);
    }

    public function activityReportData($dataInicial, $dataFinal, $leadsMes = 'todos', $idVendedor = null)
    {
        try {
            // Validar e formatar datas
            $dataInicio = Carbon::createFromFormat('Y-m-d', $dataInicial)->startOfDay();
            $dataFim = Carbon::createFromFormat('Y-m-d', $dataFinal)->endOfDay();

            // Empresa ID (você pode pegar do usuário logado ou sessão)
            $empresaId = auth()->user()->empresa_id ?? 2;

            // Query base para atividades
            $queryAtividades = DB::table('lead_atividades as la')
                ->join('contatos as c', 'c.id', '=', 'la.contato_id')
                ->join('users as u', 'u.id', '=', 'la.user_id')
                ->leftJoin('tabulacoes as t', 't.id', '=', 'la.tabulacao_atual_id')
                ->where('la.empresa_id', $empresaId)
                ->where('la.log_descricao', 'atividadeComentario')
                ->whereBetween('la.created_at', [$dataInicio, $dataFim]);

            // Filtro por tipo de lead (se leads do mês, filtrar também pela data de criação do contato)
            if ($leadsMes === 'mes') {
                $queryAtividades->whereBetween('c.created_at', [$dataInicio, $dataFim]);
            }

            // Filtro por vendedor específico
            if ($idVendedor && $idVendedor !== 'null') {
                $queryAtividades->where('la.user_id', $idVendedor);
            }

            // 1. Estatísticas gerais
            $estatisticas = $this->getEstatisticasGerais($queryAtividades, $empresaId, $dataInicio, $dataFim, $leadsMes, $idVendedor);

            // 2. Dados para gráfico de atividades por período
            $atividadesPeriodo = $this->getAtividadesPorPeriodo($queryAtividades, $dataInicio, $dataFim);

            // 3. Ranking de vendedores
            $rankingVendedores = $this->getRankingVendedores($queryAtividades);

            // 4. Distribuição por tabulação
            $distribuicaoTabulacao = $this->getDistribuicaoTabulacao($queryAtividades);

            // 5. Atividades por hora
            $atividadesHora = $this->getAtividadesPorHora($queryAtividades);

            // 6. Dados da tabela detalhada
            $tabelaDetalhes = $this->getTabelaDetalhes($queryAtividades);

            // 7. Lista de vendedores para o select
            $vendedores = $this->getVendedores($empresaId);

            return response()->json([
                'success' => true,
                'data' => [
                    'estatisticas' => $estatisticas,
                    'atividades_periodo' => $atividadesPeriodo,
                    'ranking_vendedores' => $rankingVendedores,
                    'distribuicao_tabulacao' => $distribuicaoTabulacao,
                    'atividades_hora' => $atividadesHora,
                    'tabela_detalhes' => $tabelaDetalhes,
                    'vendedores' => $vendedores
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getEstatisticasGerais($queryAtividades, $empresaId, $dataInicio, $dataFim, $leadsMes, $idVendedor)
    {
        // Total de atividades
        $totalAtividades = (clone $queryAtividades)->count();

        // Total de leads únicos
        $totalLeads = (clone $queryAtividades)->distinct('la.contato_id')->count('la.contato_id');

        // Média de atividades por lead
        $mediaAtividades = $totalLeads > 0 ? round($totalAtividades / $totalLeads, 1) : 0;

        // Vendedores ativos
        $vendedoresAtivos = (clone $queryAtividades)->distinct('la.user_id')->count('la.user_id');

        // Calcular variações (comparar com período anterior)
        $diasPeriodo = $dataInicio->diffInDays($dataFim) + 1;
        $dataInicioAnterior = $dataInicio->copy()->subDays($diasPeriodo);
        $dataFimAnterior = $dataInicio->copy()->subDay();

        $queryAnterior = DB::table('lead_atividades as la')
            ->join('contatos as c', 'c.id', '=', 'la.contato_id')
            ->where('la.empresa_id', $empresaId)
            ->where('la.log_descricao', 'atividadeComentario')
            ->whereBetween('la.created_at', [$dataInicioAnterior, $dataFimAnterior]);

        if ($leadsMes === 'mes') {
            $queryAnterior->whereBetween('c.created_at', [$dataInicioAnterior, $dataFimAnterior]);
        }

        if ($idVendedor && $idVendedor !== 'null') {
            $queryAnterior->where('la.user_id', $idVendedor);
        }

        $totalAtividadesAnterior = $queryAnterior->count();
        $variacaoAtividades = $totalAtividadesAnterior > 0 ?
            round((($totalAtividades - $totalAtividadesAnterior) / $totalAtividadesAnterior) * 100, 1) : 0;

        return [
            'total_atividades' => $totalAtividades,
            'total_leads' => $totalLeads,
            'media_atividades' => $mediaAtividades,
            'vendedores_ativos' => $vendedoresAtivos,
            'variacao_atividades' => $variacaoAtividades,
            'variacao_leads' => 0, // Calcular se necessário
            'variacao_media' => 0, // Calcular se necessário
            'variacao_vendedores' => 0 // Calcular se necessário
        ];
    }

    private function getAtividadesPorPeriodo($queryAtividades, $dataInicio, $dataFim)
    {
        $atividades = (clone $queryAtividades)
            ->select(
                DB::raw('DATE(la.created_at) as data'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('DATE(la.created_at)'))
            ->orderBy('data')
            ->get();

        // Preencher dias sem atividades
        $periodo = [];
        $current = $dataInicio->copy();

        while ($current <= $dataFim) {
            $dataFormatada = $current->format('Y-m-d');
            $atividade = $atividades->firstWhere('data', $dataFormatada);

            $periodo[] = [
                'data' => $current->format('d/m'),
                'total' => $atividade ? $atividade->total : 0
            ];

            $current->addDay();
        }

        return $periodo;
    }

    private function getRankingVendedores($queryAtividades)
    {
        return (clone $queryAtividades)
            ->select(
                'u.name as vendedor',
                DB::raw('COUNT(*) as total_atividades')
            )
            ->groupBy('u.id', 'u.name')
            ->orderBy('total_atividades', 'desc')
            ->limit(10)
            ->get();
    }

    private function getDistribuicaoTabulacao($queryAtividades)
    {
        return (clone $queryAtividades)
            ->select(
                DB::raw('COALESCE(t.descricao, "Sem Tabulação") as tabulacao'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('t.id', 't.descricao')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function getAtividadesPorHora($queryAtividades)
    {
        $atividades = (clone $queryAtividades)
            ->select(
                DB::raw('HOUR(la.created_at) as hora'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw('HOUR(la.created_at)'))
            ->orderBy('hora')
            ->get();

        // Preencher todas as horas (8h às 18h)
        $horasPeriodo = [];
        for ($h = 8; $h <= 18; $h++) {
            $atividade = $atividades->firstWhere('hora', $h);
            $horasPeriodo[] = [
                'hora' => $h . 'h',
                'total' => $atividade ? $atividade->total : 0
            ];
        }

        return $horasPeriodo;
    }

    private function getTabelaDetalhes($queryAtividades)
    {
        return (clone $queryAtividades)
            ->select(
                'c.id',
                'c.nome_cliente',
                'u.name as vendedor',
                DB::raw('COALESCE(t.descricao, "Sem Tabulação") as tabulacao'),
                DB::raw('COUNT(*) as total_atividades'),
                DB::raw('MAX(la.created_at) as ultima_atividade')
            )
            ->groupBy('c.id', 'c.nome_cliente', 'u.name', 't.descricao')
            ->orderBy('total_atividades', 'desc')
            ->paginate(50);
    }

    private function getVendedores($empresaId)
    {
        return DB::table('users')
            ->where('empresa_id', $empresaId)
            ->where('ativo', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function getLeadComentarios($leadId)
    {
        try {
            $empresaId = auth()->user()->empresa_id;

            // Buscar informações básicas do lead
            $lead = DB::table('contatos as c')
                ->leftJoin('contatos_corretores as cc', 'cc.contato_id', '=', 'c.id')
                ->leftJoin('tabulacoes as t', 't.id', '=', 'cc.tabulacao_id')
                ->where('c.id', $leadId)
                ->where('c.empresa_id', $empresaId)
                ->select(
                    'c.id',
                    'c.nome_cliente',
                    'c.telefone1',
                    'c.email',
                    'c.created_at as data_criacao',
                    't.descricao as tabulacao_atual'
                )
                ->first();

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead não encontrado'
                ], 404);
            }

            // Buscar todos os comentários do lead
            $comentarios = DB::table('comentarios as co')
                ->leftJoin('users as u', 'u.id', '=', 'co.user_id')
                ->where('co.contato_id', $leadId)
                ->where('co.empresa_id', $empresaId)
                ->where('co.visivel', 'Y')
                ->select(
                    'co.id',
                    'co.anotacao',
                    'co.legado',
                    'co.supervisao',
                    'co.created_at',
                    'u.name as usuario',
                    DB::raw('COALESCE(u.name, "Sistema") as autor')
                )
                ->orderBy('co.created_at', 'desc')
                ->get();

            // Buscar histórico de atividades/tabulações
            $atividades = DB::table('lead_atividades as la')
                ->leftJoin('users as u', 'u.id', '=', 'la.user_id')
                ->leftJoin('tabulacoes as ta', 'ta.id', '=', 'la.tabulacao_anterior_id')
                ->leftJoin('tabulacoes as tb', 'tb.id', '=', 'la.tabulacao_atual_id')
                ->where('la.contato_id', $leadId)
                ->where('la.empresa_id', $empresaId)
                ->select(
                    'la.id',
                    'la.log_descricao',
                    'la.created_at',
                    'u.name as usuario',
                    'ta.descricao as tabulacao_anterior',
                    'tb.descricao as tabulacao_atual'
                )
                ->orderBy('la.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'lead' => $lead,
                    'comentarios' => $comentarios,
                    'atividades' => $atividades
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar comentários: ' . $e->getMessage()
            ], 500);
        }
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
                'operadoras' => $this->getOperadoras($filtros)
            ]
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
                'to' => $vendas->lastItem()
            ]
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
            'empresa_id' => Auth::user()->empresa_id
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
            }
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
            'vidas_por_contrato' => $query->avg('vidas') ?? 0
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
            ->where('ativo', "Y")
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
            'anos' => $anos
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
            if (!$vendedorId) {

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
                    'detalhes_vendedor' => $detalhesVendedor
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados: ' . $e->getMessage()
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
            ->whereYear('vendas.created_at', $ano);

        if ($vendedorId) {
            $queryVendas->where('user_id', $vendedorId);
        }

        $totalVendas = $queryVendas->count();
        $valorTotal = $queryVendas->sum('valor_contrato') ?? 0;
        $ticketMedio = $totalVendas > 0 ? $valorTotal / $totalVendas : 0;

        // Taxa de conversão
        $taxaConversao = $totalLeadsUnicos > 0 ? round(($totalVendas / $totalLeadsUnicos) * 100, 2) : 0;

        return [
            'total_leads_unicos' => $totalLeadsUnicos,
            'total_vendas' => $totalVendas,
            'valor_total' => $valorTotal,
            'ticket_medio' => $ticketMedio,
            'taxa_conversao' => $taxaConversao
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
                ->whereYear('vendas.created_at', $ano)
                ->whereRaw('MONTH(vendas.created_at) BETWEEN ? AND ?', [$mesInicio, $mesFim]);

            if ($vendedorId) {
                $queryVendas->where('user_id', $vendedorId);
            }

            $totalVendas = $queryVendas->count();
            $valorTotal = $queryVendas->sum('valor_contrato') ?? 0;
            $ticketMedio = $totalVendas > 0 ? $valorTotal / $totalVendas : 0;
            $taxaConversao = $leadsUnicos > 0 ? round(($totalVendas / $leadsUnicos) * 100, 2) : 0;

            $trimestres[] = [
                'trimestre' => $trimestre,
                'periodo' => "Q{$trimestre} ({$ano})",
                'leads_unicos' => $leadsUnicos,
                'total_vendas' => $totalVendas,
                'valor_total' => $valorTotal,
                'ticket_medio' => $ticketMedio,
                'taxa_conversao' => $taxaConversao
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
                ->whereYear('vendas.created_at', $ano)
                ->whereMonth('vendas.created_at', $mes);

            if ($vendedorId) {
                $queryVendas->where('user_id', $vendedorId);
            }

            $totalVendas = $queryVendas->count();
            $valorTotal = $queryVendas->sum('valor_contrato') ?? 0;

            $meses[] = [
                'mes' => $mes,
                'nome_mes' => $nomesMeses[$mes - 1],
                'leads_unicos' => $leadsUnicos,
                'total_vendas' => $totalVendas,
                'valor_total' => $valorTotal
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
                DB::raw('SUM(valor_contrato) as valor_total')
            )
            ->whereHas('user', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
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
                DB::raw('SUM(valor_contrato) as valor_total'),
                DB::raw('AVG(valor_contrato) as ticket_medio')
            )
            ->whereHas('user', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
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
                'taxa' => $taxa
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
                'user_id',
                DB::raw('COUNT(DISTINCT v.id) as total_vendas'),
                DB::raw('SUM(v.valor_contrato) as valor_total'),
                DB::raw('AVG(v.valor_contrato) as ticket_medio')
            )
            ->whereYear('created_at', $ano)
            ->groupBy('user_id');

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

        if (!$vendedor) {
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
            'planos_favoritos' => $planosVendedor
        ];
    }

    /**
     * Relatório de Distribuição de Leads
     */
    public function distribuicaoLeads()
    {
        return view('content.pages.relatorios.distribuicao-leads');
    }

    /**
     * Dados do Relatório de Distribuição de Leads
     */
    public function distribuicaoLeadsData(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $dataInicial = $request->input('data_inicial');
        $dataFinal = $request->input('data_final');

        // Total de leads no sistema
        $totalLeadsQuery = DB::table('contatos')
            ->where('empresa_id', $empresaId);

        if ($dataInicial && $dataFinal) {
            $totalLeadsQuery->whereBetween('created_at', [$dataInicial, $dataFinal]);
        }

        $totalLeads = $totalLeadsQuery->count();

        // Leads em contatos_corretores (distribuídos aos vendedores)
        $leadsDistribuidosQuery = DB::table('contatos_corretores')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->where('contatos_corretores.empresa_id', $empresaId);

        if ($dataInicial && $dataFinal) {
            $leadsDistribuidosQuery->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

        $leadsDistribuidos = $leadsDistribuidosQuery->distinct()->count('contatos_corretores.contato_id');

        // Leads sendo trabalhados por vendedores (tipo_tabulacao = 'C')
        $leadsComercialQuery = DB::table('contatos_corretores')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where('tabulacoes.tipo_tabulacao', 'C');

        if ($dataInicial && $dataFinal) {
            $leadsComercialQuery->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

        $leadsComercial = $leadsComercialQuery->distinct()->count('contatos_corretores.contato_id');

        // Leads sob custódia administrativa (tipo_tabulacao = 'A')
        $leadsAdministrativoQuery = DB::table('contatos_corretores')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where('tabulacoes.tipo_tabulacao', 'A');

        if ($dataInicial && $dataFinal) {
            $leadsAdministrativoQuery->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

        $leadsAdministrativo = $leadsAdministrativoQuery->distinct()->count('contatos_corretores.contato_id');

        // Leads na preditiva
        $leadsPreditivaQuery = DB::table('preditiva')
            ->join('contatos', 'preditiva.contato_id', '=', 'contatos.id')
            ->where('preditiva.empresa_id', $empresaId);

        if ($dataInicial && $dataFinal) {
            $leadsPreditivaQuery->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

        $leadsPreditiva = $leadsPreditivaQuery->count();

        // Leads descartados (status = 'N')
        $leadsDescartadosQuery = DB::table('contatos')
            ->where('empresa_id', $empresaId)
            ->where('status', 'N');

        if ($dataInicial && $dataFinal) {
            $leadsDescartadosQuery->whereBetween('created_at', [$dataInicial, $dataFinal]);
        }

        $leadsDescartados = $leadsDescartadosQuery->count();

        // Distribuição por status (tabulações) - Todos
        $distribuicaoPorStatus = DB::table('contatos_corretores')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->select('tabulacoes.descricao', DB::raw('COUNT(DISTINCT contatos_corretores.contato_id) as total'))
            ->where('contatos_corretores.empresa_id', $empresaId);

        if ($dataInicial && $dataFinal) {
            $distribuicaoPorStatus->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

        $distribuicaoPorStatus = $distribuicaoPorStatus
            ->groupBy('tabulacoes.descricao')
            ->orderBy('total', 'desc')
            ->get();

        // Distribuição Comercial - Tabulações específicas de custódia do vendedor
        $tabulacoesComerciais = [
            'PROSPECÇÃO',
            'REUNIÃO',
            'NEGOCIAÇÃO',
            'DOCUMENTO',
            'NEGOCIO FECHADO',
            'NEGOCIO NAO FECHADO',
            'FOLLOW-UP',
            'SEM CONTATO',
            'NOVOS CLIENTES',
            'AGENDAMENTO'
        ];

        $distribuicaoComercial = DB::table('contatos_corretores')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->select('tabulacoes.descricao', DB::raw('COUNT(DISTINCT contatos_corretores.contato_id) as total'))
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->whereIn('tabulacoes.descricao', $tabulacoesComerciais);

        if ($dataInicial && $dataFinal) {
            $distribuicaoComercial->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

        $distribuicaoComercial = $distribuicaoComercial
            ->groupBy('tabulacoes.descricao')
            ->orderBy('total', 'desc')
            ->get();

        // Distribuição Administrativa - Tabulações específicas de custódia administrativa
        $tabulacoesAdministrativas = [
            'VENDA',
            'ESTORNO',
            'IMPLANTADO',
            'DECLINADO',
            'ANALISE DE DOCUMENTOS',
            'PENDENCIA',
            'CONTR. GERADO - AGUARDANDO ASSINATURA',
            'REGULARIZADO',
            'BOLETO DISPONIVEL',
            'ANALISE OPERADORA',
            'AGUARD. ASSINATURA DA DS'
        ];

        $distribuicaoAdministrativa = DB::table('contatos_corretores')
            ->join('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
            ->join('contatos', 'contatos_corretores.contato_id', '=', 'contatos.id')
            ->select('tabulacoes.descricao', DB::raw('COUNT(DISTINCT contatos_corretores.contato_id) as total'))
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->whereIn('tabulacoes.descricao', $tabulacoesAdministrativas);

        if ($dataInicial && $dataFinal) {
            $distribuicaoAdministrativa->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

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
            ->where(function($query) {
                $query->where('tabulacoes.sub_tabulacao', 'Y')
                      ->orWhere('tabulacoes.descricao', 'REMARKETING');
            });

        if ($dataInicial && $dataFinal) {
            $distribuicaoDescarte->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

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
            ->where(function($query) {
                $query->where('tabulacoes.sub_tabulacao', 'Y')
                      ->orWhere('tabulacoes.descricao', 'REMARKETING');
            });

        if ($dataInicial && $dataFinal) {
            $motivosDescarte->whereBetween('contatos.created_at', [$dataInicial, $dataFinal]);
        }

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

        return response()->json([
            'resumo' => [
                'total_leads' => $totalLeads,
                'leads_distribuidos' => $leadsDistribuidos,
                'leads_comercial' => $leadsComercial,
                'leads_administrativo' => $leadsAdministrativo,
                'leads_preditiva' => $leadsPreditiva,
                'leads_descartados' => $leadsDescartados,
                'tentativas_preditiva' => $tentativasPreditiva
            ],
            'distribuicao_status' => $distribuicaoPorStatus,
            'distribuicao_comercial' => $distribuicaoComercial,
            'distribuicao_administrativa' => $distribuicaoAdministrativa,
            'distribuicao_descarte' => $distribuicaoDescarte,
            'motivos_descarte' => $motivosDescarte
        ]);
    }

}
