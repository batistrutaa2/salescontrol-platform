<?php

namespace App\Http\Controllers\pages\vendas;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Operadora;
use App\Models\Tabulacoes;
use App\Models\User;
use App\Models\VendaHistorico;
use App\Models\Vendas as VendasModel;
use App\Notifications\VendaReenviadaNotification;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Eloquent\VendasRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Vendas extends Controller
{
    protected VendasRepository $repositoryVendas;

    protected UsuariosRepository $usuariosRepository;

    protected ContatosCorretoresRepository $contatosCorretoresRepository;

    public function __construct(
        VendasRepositoryInterface $vendasRepositoryInterface,
        UsuariosRepositoryInterface $usuariosRepositoryInterface,
        ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface
    ) {

        $this->repositoryVendas = $vendasRepositoryInterface;
        $this->usuariosRepository = $usuariosRepositoryInterface;
        $this->contatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
    }

    public function index()
    {
        return view('content.pages.vendas.index', [
            'anosDisponiveis' => $this->getAnosDisponiveis(Auth::user()->empresa_id),
        ]);
    }

    public function salesOfTheMonth()
    {
        $vendas = $this->repositoryVendas->vendasDoMesAnoAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);

        return response()->json(['data' => $vendas]);
    }

    public function monthlySalesFilter($name_user = null)
    {
        try {

            if (is_null($name_user)) {
                $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
                $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
                $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
                $percentualConversaoMes = $this->repositoryVendas->conversaoMensal(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
                $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
            } else {
                $user = $this->usuariosRepository->getUserSearchName($name_user);

                if (is_null($user)) {
                    return response()->json(['error' => true]);
                }

                $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual($user->id, $user->empresa_id, $user->user_role_id);
                $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual($user->id, $user->empresa_id, $user->user_role_id);
                $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual($user->id, $user->empresa_id, $user->user_role_id);
                $percentualConversaoMes = $this->repositoryVendas->conversaoMensal($user->id, $user->empresa_id, $user->user_role_id);
                $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes($user->id, $user->empresa_id, $user->user_role_id);
            }

            $response = [
                'vendasCadastradasMes' => $vendasCadastradasMes,
                'vendasImplantadasMes' => $vendasImplantadasMes,
                'vendasEstornadasMes' => $vendasEstornadasMes,
                'percentualConversaoMes' => $percentualConversaoMes,
                'totalContatosMes' => $totalContatosMes,
                'error' => false,
            ];

            return response()->json($response);
        } catch (\Throwable $th) {
            return response()->json(['error' => true]);
        }
    }

    public function analyticalSales()
    {
        return view('content.pages.vendas.analyticalSales');
    }

    public function dados(Request $request)
    {
        $filtros = $this->aplicarFiltros($request);

        return response()->json([
            'success' => true,
            'data' => [
                'vendas_totais' => $this->getVendasTotais($filtros),
                'vendas_por_mes' => $this->getVendasPorMes($filtros),
                'vendas_por_vendedor' => $this->getVendasPorVendedor($filtros),
                'vendas_por_operadora' => $this->getVendasPorOperadora($filtros),
                'vendas_por_plano' => $this->getVendasPorPlano($filtros),
                'vendas_por_entidade' => $this->getVendasPorEntidade($filtros),
                'resumo_geral' => $this->getResumoGeral($filtros),
                'vendedores' => $this->getVendedoresPorEmpresa(),
                'anos_disponiveis' => $this->getAnosDisponiveis($filtros),
                'operadoras' => $this->getOperadoras($filtros),
                'status_disponiveis' => $this->getStatusDisponiveis(),
            ],
        ]);
    }

    public function listarVendas(Request $request)
    {
        $filtros = $this->aplicarFiltros($request);
        $filtros['status'] = $request->get('status'); // Filtro de status apenas para listagem
        $perPage = $request->get('per_page', 20);

        $vendas = $this->getVendasTotais($filtros, $perPage);

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

    public function exportar(Request $request)
    {
        $filtros = $this->aplicarFiltros($request);
        $vendas = $this->getVendasTotais($filtros);

        return response()->json([
            'success' => true,
            'message' => 'Exportação em desenvolvimento',
            'total_registros' => $vendas->count(),
        ]);
    }

    public function downloadBoleto($id)
    {
        $venda = VendasModel::findOrFail($id);

        $path = trim($venda->path_boleto_disponivel ?? '', '/');
        if ($path === '') {
            abort(404);
        }

        $downloadName = 'boleto-'.$venda->nome_contrato.'.'.(pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf');
        if (Storage::exists($path)) {
            return Storage::download($path, $downloadName);
        }

        abort(404);
    }

    private function aplicarFiltros($request)
    {
        return [
            'ano' => $request->get('ano'),
            'mes' => $request->get('mes'),
            'vendedor_id' => $request->get('vendedor_id'),
            'operadora' => $request->get('operadora'),
            'data_inicio' => $request->get('data_inicio'),
            'data_fim' => $request->get('data_fim'),
            'empresa_id' => Auth::user()->empresa_id, // Filtro automático por empresa
        ];
    }

    private function aplicarFiltroEmpresa($query)
    {
        $empresaId = Auth::user()->empresa_id;

        // Filtrar vendas apenas da empresa do usuário logado
        $query->whereHas('user', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        });

        return $query;
    }

    private function getVendasTotais($filtros, $perPage = null)
    {
        $query = VendasModel::with([
            'user' => function ($query) {
                $query->select('id', 'name', 'empresa_id');
            },
            'contatoCorretor.tabulacao',
        ]);

        // Aplicar filtro por empresa PRIMEIRO
        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusVenda($query); // ✅ filtro de status

        // Aplicar outros filtros
        if ($filtros['ano']) {
            $query->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.created_at', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if (isset($filtros['status']) && $filtros['status']) {
            $query->whereHas('contatoCorretor', function ($q) use ($filtros) {
                $q->where('tabulacao_id', $filtros['status']);
            });
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        $query->orderBy('vendas.created_at', 'desc');

        if ($perPage) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    private function getVendasPorMes($filtros)
    {
        $query = VendasModel::select(
            DB::raw('MONTH(created_at) as mes'),
            DB::raw('YEAR(created_at) as ano'),
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw("SUM(CASE WHEN YEAR(created_at) >= 2026 THEN valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END ELSE valor_contrato END) as valor_total"),
            DB::raw('SUM(vidas) as total_vidas')
        );

        // Aplicar filtro por empresa
        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusVenda($query); // ✅ filtro de status

        if ($filtros['ano']) {
            $query->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return $query->groupBy('ano', 'mes')
            ->orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->get();
    }

    private function getVendasPorVendedor($filtros)
    {
        $empresaId = Auth::user()->empresa_id;

        $query = VendasModel::select(
            'users.name as vendedor',
            DB::raw('COUNT(*) as total_vendas'),
            // valor_total agrega valor_contrato + angariação (mesmo critério do dashboard).
            DB::raw("SUM(vendas.valor_contrato + CASE WHEN vendas.angariacao_status = 'SIM' THEN COALESCE(vendas.angariacao_valor, 0) ELSE 0 END) as valor_total"),
            DB::raw("SUM(CASE WHEN vendas.angariacao_status = 'SIM' THEN COALESCE(vendas.angariacao_valor, 0) ELSE 0 END) as valor_angariacao"),
            DB::raw('SUM(vidas) as total_vidas')
        )->join('users', 'vendas.user_id', '=', 'users.id')
            ->where('users.empresa_id', $empresaId); // Filtro por empresa

        if ($filtros['ano']) {
            $query->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.created_at', $filtros['mes']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        $query = $this->aplicarFiltroStatusVenda($query);

        return $query->groupBy('users.id', 'users.name')
            ->orderBy('valor_total', 'desc')
            ->get();
    }

    private function getVendasPorOperadora($filtros)
    {
        $query = VendasModel::select(
            'operadora',
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw("SUM(CASE WHEN YEAR(created_at) >= 2026 THEN valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END ELSE valor_contrato END) as valor_total"),
            DB::raw('SUM(vidas) as total_vidas')
        );

        // Aplicar filtro por empresa
        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusVenda($query);

        if ($filtros['ano']) {
            $query->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.created_at', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return $query->whereNotNull('operadora')
            ->groupBy('operadora')
            ->orderBy('valor_total', 'desc')
            ->get();
    }

    private function getVendasPorPlano($filtros)
    {
        $query = VendasModel::select(
            'nome_plano',
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw("SUM(CASE WHEN YEAR(created_at) >= 2026 THEN valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END ELSE valor_contrato END) as valor_total")
        );

        // Aplicar filtro por empresa
        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusVenda($query); // ✅ filtro de status

        if ($filtros['ano']) {
            $query->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.created_at', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return $query->whereNotNull('nome_plano')
            ->groupBy('nome_plano')
            ->orderBy('total_vendas', 'desc')
            ->get();
    }

    private function getVendasPorEntidade($filtros)
    {
        $empresaId = Auth::user()->empresa_id;

        $query = VendasModel::select(
            DB::raw("COALESCE(NULLIF(TRIM(contatos.entidade), ''), 'NÃO INFORMADO') as entidade"),
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw("SUM(CASE WHEN YEAR(vendas.created_at) >= 2026 THEN vendas.valor_contrato + CASE WHEN vendas.angariacao_status = 'SIM' THEN COALESCE(vendas.angariacao_valor, 0) ELSE 0 END ELSE vendas.valor_contrato END) as valor_total"),
            DB::raw('SUM(vendas.vidas) as total_vidas')
        )
            ->join('contatos', 'vendas.contato_id', '=', 'contatos.id')
            ->join('users', 'vendas.user_id', '=', 'users.id')
            ->where('users.empresa_id', $empresaId);

        // Aplicar filtro de status de venda
        $query->whereHas('contatoCorretor', function ($q) {
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

        if ($filtros['ano']) {
            $query->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.created_at', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('vendas.user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('vendas.operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        return $query->groupBy(DB::raw("COALESCE(NULLIF(TRIM(contatos.entidade), ''), 'NÃO INFORMADO')"))
            ->orderBy('total_vendas', 'desc')
            ->get();
    }

    private function getResumoGeral($filtros)
    {
        $query = VendasModel::query();

        // Aplicar filtro por empresa
        $query = $this->aplicarFiltroEmpresa($query);
        $query = $this->aplicarFiltroStatusVenda($query); // ✅ filtro de status

        if ($filtros['ano']) {
            $query->whereYear('vendas.created_at', $filtros['ano']);
        }

        if ($filtros['mes']) {
            $query->whereMonth('vendas.created_at', $filtros['mes']);
        }

        if ($filtros['vendedor_id']) {
            $query->where('user_id', $filtros['vendedor_id']);
        }

        if ($filtros['operadora']) {
            $query->where('operadora', $filtros['operadora']);
        }

        if ($filtros['data_inicio'] && $filtros['data_fim']) {
            $query->whereBetween('vendas.created_at', [$filtros['data_inicio'], $filtros['data_fim']]);
        }

        // Query para vendas implantadas
        $queryImplantadas = clone $query;
        $queryImplantadas->whereHas('contatoCorretor', function ($q) {
            $q->where('tabulacao_id', Tabulations::IMPLANTADO);
        });

        // Calcular angariação total
        $valorAngariacao = (clone $query)->where('angariacao_status', 'SIM')
            ->sum('angariacao_valor') ?? 0;

        // Calcular angariação implantada
        $valorAngariacaoImplantada = (clone $queryImplantadas)->where('angariacao_status', 'SIM')
            ->sum('angariacao_valor') ?? 0;

        // Calcular valor total cadastrado (valor_contrato + angariação) — mesmo critério do dashboard.
        $valorTotal = ((clone $query)->sum('valor_contrato') ?? 0) + $valorAngariacao;

        // Calcular valor implantado (somente valor_contrato)
        $valorImplantado = (clone $queryImplantadas)->sum('valor_contrato') ?? 0;

        return [
            'total_contratos' => $query->count(),
            'valor_total' => $valorTotal,
            'valor_angariacao' => $valorAngariacao,
            'valor_angariacao_implantada' => $valorAngariacaoImplantada,
            'valor_implantado' => $valorImplantado,
            'total_vidas' => $query->sum('vidas') ?? 0,
            'ticket_medio' => $query->avg('valor_contrato') ?? 0,
        ];
    }

    private function getVendedoresPorEmpresa()
    {
        $empresaId = Auth::user()->empresa_id;

        return User::where('ativo', 'Y')
            ->where('empresa_id', $empresaId)
            ->where('user_role_id', UserRole::VENDEDOR)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function getAnosDisponiveis($filtros)
    {
        $query = VendasModel::select(DB::raw('YEAR(vendas.created_at) as ano'));
        $query = $this->aplicarFiltroEmpresa($query);

        return $query->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano');
    }

    private function getOperadoras($filtros)
    {
        $query = VendasModel::select('operadora');
        $query = $this->aplicarFiltroEmpresa($query);

        return $query->whereNotNull('operadora')
            ->where('operadora', '!=', '')
            ->distinct()
            ->orderBy('operadora')
            ->pluck('operadora');
    }

    private function getStatusDisponiveis()
    {
        return Tabulacoes::whereIn('id', [
            Tabulations::VENDA,
            Tabulations::IMPLANTADO,
            Tabulations::PENDENCIA,
            Tabulations::ANALISE_OPERADORA,
            Tabulations::BOLETO_DISPONIVEL,
            Tabulations::REGULARIZADO,
            Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
            Tabulations::ANALISE_DOCUMENTOS,
            Tabulations::AGUARD_ASSINATURA_DS,
        ])
            ->select('id', 'descricao')
            ->orderBy('descricao')
            ->get();
    }

    public function getSalesAnalytical(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');

        $sales = $this->repositoryVendas->getSalesAnalytical(Auth::user()->empresa_id, $month, $year);

        return response()->json($sales);
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

    public function getResultsBroker(Request $request)
    {
        $dataInicio = $request->data_inicio;
        $dataFim = $request->data_fim;

        $vendasCadastradasMes = VendasModel::where('user_id', Auth::user()->id)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->selectRaw("SUM(valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END) as total")
            ->value('total') ?? 0;

        $vendasImplantadasMes = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
            ->where('b.tabulacao_id', Tabulations::IMPLANTADO)
            ->where('a.user_id', Auth::user()->id)
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('a.created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->selectRaw("SUM(a.valor_contrato + CASE WHEN a.angariacao_status = 'SIM' THEN COALESCE(a.angariacao_valor, 0) ELSE 0 END) as total")
            ->value('total') ?? 0;

        $quantidadeContatosMes = DB::table('contatos as a')
            ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
            ->where('b.user_id', auth()->user()->id)
            ->where('b.empresa_id', Auth::user()->empresa_id)
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('a.created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->count();

        $quantidadeVendasMes = VendasModel::where('user_id', Auth::user()->id)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->count();

        $conversao = $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);

        $vendas = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as b', 'a.contato_id', '=', 'b.contato_id')
            ->leftJoin('tabulacoes as c', 'c.id', '=', 'b.tabulacao_id')
            ->leftJoin('users as backoffice', 'backoffice.id', '=', 'a.backoffice_id')
            ->where('a.user_id', auth()->user()->id)
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('a.created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->select(
                'a.id',
                'a.nome_contrato',
                'c.descricao',
                'a.valor_contrato',
                'a.angariacao_status',
                'a.angariacao_valor',
                'a.created_at',
                'a.motivo_pendencia',
                'a.path_boleto_disponivel',
                'b.tabulacao_id',
                'a.boas_vindas_enviado_em',
                'backoffice.name as backoffice_nome'
            )
            ->get();

        return response()->json([
            'vendasCadastradasMes' => $vendasCadastradasMes ?? 0,
            'vendasImplantadasMes' => $vendasImplantadasMes ?? 0,
            'quantidadeContatosMes' => $quantidadeContatosMes ?? 0,
            'conversao' => $conversao,
            'vendas' => $vendas,
        ]);
    }

    private function calculoConversao($quantidadeContatos, $quantidadeVendas)
    {
        try {
            if ($quantidadeVendas == 0) {
                return 0.0;
            }

            $conversao = ($quantidadeVendas / $quantidadeContatos) * 100;

            return '%'.number_format($conversao, 2, ',', '.');
        } catch (\Throwable $th) {
            return 0.0;
        }
    }

    public function detalhesVenda($id)
    {
        try {
            $venda = DB::table('vendas')
                ->leftJoin('users', 'vendas.user_id', '=', 'users.id')
                ->leftJoin('users as backoffice', 'backoffice.id', '=', 'vendas.backoffice_id')
                ->leftJoin('contatos_corretores', 'vendas.contato_id', '=', 'contatos_corretores.contato_id')
                ->leftJoin('tabulacoes', 'contatos_corretores.tabulacao_id', '=', 'tabulacoes.id')
                ->where('vendas.id', $id)
                ->where('vendas.empresa_id', Auth::user()->empresa_id)
                ->select(
                    'vendas.*',
                    'users.name as vendedor_nome',
                    'backoffice.name as backoffice_nome',
                    'tabulacoes.descricao'
                )
                ->first();

            if (! $venda) {
                return response()->json(['error' => 'Venda não encontrada'], 404);
            }

            return response()->json($venda);
        } catch (\Throwable $th) {
            return response()->json(['error' => 'Erro ao buscar detalhes da venda: '.$th->getMessage()], 500);
        }
    }

    // =====================================================================
    // ESTORNO — fluxo BO ↔ Vendedor
    // =====================================================================

    public function meusEstornos()
    {
        return view('content.pages.vendas.meus-estornos');
    }

    public function meusEstornosDados(Request $request)
    {
        $user = Auth::user();

        $query = VendasModel::query()
            ->select([
                'vendas.id',
                'vendas.numero_proposta',
                'vendas.nome_contrato',
                'vendas.cpf_cnpj',
                'vendas.operadora',
                'vendas.nome_plano',
                'vendas.valor_contrato',
                'vendas.vidas',
                'vendas.motivo_pendencia',
                'vendas.user_id',
                'vendas.empresa_id',
                'vendas.created_at as venda_criada_em',
                'vendas.updated_at as venda_atualizada_em',
                'users.name as vendedor_nome',
                'backoffice.name as backoffice_nome',
            ])
            ->join('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
            ->leftJoin('users', 'users.id', '=', 'vendas.user_id')
            ->leftJoin('users as backoffice', 'backoffice.id', '=', 'vendas.backoffice_id')
            ->where('vendas.empresa_id', $user->empresa_id)
            ->where('contatos_corretores.tabulacao_id', Tabulations::ESTORNO);

        if ($user->user_role_id === UserRole::VENDEDOR) {
            $query->where('vendas.user_id', $user->id);
        }

        $vendas = $query->orderBy('vendas.updated_at', 'desc')->get();

        // Anexa data e responsável do estorno via último registro de histórico para tabulacao_nova_id = ESTORNO.
        $vendaIds = $vendas->pluck('id')->all();
        $historicos = VendaHistorico::whereIn('venda_id', $vendaIds)
            ->where('tabulacao_nova_id', Tabulations::ESTORNO)
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('venda_id');

        $vendas = $vendas->map(function ($venda) use ($historicos) {
            $hist = $historicos->get($venda->id)?->first();
            $venda->estornado_em = $hist?->created_at?->format('d/m/Y H:i');
            $venda->estornado_por_id = $hist?->user_id;

            return $venda;
        });

        return response()->json([
            'success' => true,
            'data' => $vendas,
            'total' => $vendas->count(),
        ]);
    }

    private function gateEstorno(int $vendaId): VendasModel
    {
        $user = Auth::user();

        $venda = VendasModel::with('contatoCorretor')
            ->where('id', $vendaId)
            ->where('empresa_id', $user->empresa_id)
            ->firstOrFail();

        // ADM e DEVELOPER editam qualquer venda em estorno; VENDEDOR só a própria.
        if (! in_array($user->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true)) {
            abort_unless(
                $user->user_role_id === UserRole::VENDEDOR && $venda->user_id === $user->id,
                403,
                'Você não tem permissão para corrigir esta venda.'
            );
        }

        abort_unless(
            optional($venda->contatoCorretor)->tabulacao_id === Tabulations::ESTORNO,
            403,
            'Esta venda não está em estorno.'
        );

        return $venda;
    }

    public function editEstorno($id)
    {
        $venda = $this->gateEstorno((int) $id);

        $venda->load([
            'titulares.dependentes',
            'titulares.operadoraAnterior',
            'portabilidades.operadoraAnterior',
            'contatoCorretor',
        ]);

        $cliente = DB::table('contatos')->where('id', $venda->contato_id)->first();

        $operadoras = Operadora::where('status', 'Y')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('content.pages.vendas.edit-estorno', [
            'venda' => $venda,
            'cliente' => $cliente,
            'operadoras' => $operadoras,
        ]);
    }

    public function reenviarEstorno(Request $request, $id)
    {
        $venda = $this->gateEstorno((int) $id);

        // Mesmo conjunto de validações da nova proposta. Observação do reenvio é opcional —
        // alinhamentos podem acontecer por canais internos (telefone/whatsapp) e o vendedor
        // só precisa devolver a venda para o backoffice continuar a tratativa.
        $request->validate([
            'observacao_reenvio' => 'nullable|string|max:500',
            'nome_contrato' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string',
            'operadora_id' => 'required|integer|exists:operadoras,id',
            'titulares' => 'required|array|min:1',
            'titulares.*.nome' => 'required|string|max:100',
            'titulares.*.plano_id' => 'required|integer|exists:planos,id',
        ], [
            'nome_contrato.required' => 'Razão Social é obrigatória.',
            'cpf_cnpj.required' => 'CNPJ/CPF é obrigatório.',
            'operadora_id.required' => 'Selecione uma operadora.',
            'titulares.required' => 'Adicione pelo menos um titular.',
            'titulares.min' => 'Adicione pelo menos um titular.',
            'titulares.*.nome.required' => 'Nome do titular é obrigatório.',
            'titulares.*.plano_id.required' => 'Selecione o plano para todos os titulares.',
        ]);

        $observacaoReenvio = trim((string) $request->input('observacao_reenvio')) ?: null;

        try {
            DB::beginTransaction();

            // 1) Atualiza a venda + filhos com o payload completo.
            $this->repositoryVendas->updateContractFull($venda->id, $request->all());

            // 2) Move tabulação do contato_corretor: ESTORNO → VENDA + grava histórico.
            $ok = $this->contatosCorretoresRepository->alterStatusContract(
                $venda->contato_id,
                Tabulations::VENDA,
                $observacaoReenvio,
                null
            );

            if (! $ok) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->with('status', 'error')
                    ->with('message', 'Não foi possível reenviar a venda. Tente novamente.');
            }

            // 3) Limpa motivo da venda — venda volta ao fluxo normal.
            VendasModel::where('id', $venda->id)->update([
                'motivo_pendencia' => null,
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Erro ao reenviar venda: '.$th->getMessage());
        }

        // 4) Notifica o backoffice dono.
        if ($venda->backoffice_id) {
            $bo = User::find($venda->backoffice_id);
            if ($bo) {
                $bo->notify(new VendaReenviadaNotification(
                    vendaId: $venda->id,
                    nomeContrato: $request->input('nome_contrato') ?? $venda->nome_contrato ?? "#{$venda->id}",
                    vendedorNome: Auth::user()->name ?? null,
                    observacaoReenvio: $observacaoReenvio,
                ));
            }
        }

        return redirect()->route('sale.meusEstornos')
            ->with('status', 'success')
            ->with('message', 'Venda corrigida e reenviada para o backoffice.');
    }
}
