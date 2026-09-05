<?php

namespace App\Http\Controllers\pages\vendas;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Operadora;
use App\Models\Plano;
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
use App\Services\TabulationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Vendas extends Controller
{
    protected VendasRepository $repositoryVendas;

    protected UsuariosRepository $usuariosRepository;

    protected ContatosCorretoresRepository $contatosCorretoresRepository;

    protected TabulationCatalog $tabulationCatalog;

    public function __construct(
        VendasRepositoryInterface $vendasRepositoryInterface,
        UsuariosRepositoryInterface $usuariosRepositoryInterface,
        ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface,
        TabulationCatalog $tabulationCatalog
    ) {

        $this->repositoryVendas = $vendasRepositoryInterface;
        $this->usuariosRepository = $usuariosRepositoryInterface;
        $this->contatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
        $this->tabulationCatalog = $tabulationCatalog;
    }

    public function index()
    {
        return view('content.pages.vendas.index', [
            'anosDisponiveis' => $this->getAnosDisponiveis($this->tenantId()),
        ]);
    }

    public function salesOfTheMonth()
    {
        $vendas = $this->repositoryVendas->vendasDoMesAnoAtual(Auth::user()->id, $this->tenantId(), Auth::user()->user_role_id);

        return response()->json(['data' => $vendas]);
    }

    public function monthlySalesFilter($name_user = null)
    {
        try {

            if (is_null($name_user)) {
                $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual(Auth::user()->id, $this->tenantId(), Auth::user()->user_role_id);
                $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual(Auth::user()->id, $this->tenantId(), Auth::user()->user_role_id);
                $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual(Auth::user()->id, $this->tenantId(), Auth::user()->user_role_id);
                $percentualConversaoMes = $this->repositoryVendas->conversaoMensal(Auth::user()->id, $this->tenantId(), Auth::user()->user_role_id);
                $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes(Auth::user()->id, $this->tenantId(), Auth::user()->user_role_id);
            } else {
                $user = $this->usuariosRepository->getUserSearchName($name_user);

                if (is_null($user)) {
                    return response()->json(['error' => true]);
                }

                $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual($user->id, $this->tenantId(), $user->user_role_id);
                $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual($user->id, $this->tenantId(), $user->user_role_id);
                $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual($user->id, $this->tenantId(), $user->user_role_id);
                $percentualConversaoMes = $this->repositoryVendas->conversaoMensal($user->id, $this->tenantId(), $user->user_role_id);
                $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes($user->id, $this->tenantId(), $user->user_role_id);
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
        $perPage = $filtros['per_page'] ?? 20;

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
        $user = Auth::user();
        $venda = VendasModel::where('empresa_id', $this->tenantId())->findOrFail($id);

        abort_unless(
            $user->isPlatformAdmin()
                || in_array((int) $user->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE, UserRole::SUPERVISOR, UserRole::DEVELOPER], true)
                || ((int) $user->user_role_id === UserRole::VENDEDOR && (int) $venda->user_id === (int) $user->id),
            403
        );

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
        $empresaId = (int) $this->tenantId();
        $dados = $request->validate([
            'ano' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('is_platform_admin', false)
                    ->where('user_role_id', UserRole::VENDEDOR)),
            ],
            'operadora' => ['nullable', 'string', 'max:255'],
            'data_inicio' => ['nullable', 'date_format:Y-m-d', 'required_with:data_fim'],
            'data_fim' => ['nullable', 'date_format:Y-m-d', 'required_with:data_inicio', 'after_or_equal:data_inicio'],
            'status' => [
                'nullable',
                'integer',
                Rule::exists('tabulacoes', 'id')->where('empresa_id', $empresaId),
            ],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        return [
            'ano' => $dados['ano'] ?? null,
            'mes' => $dados['mes'] ?? null,
            'vendedor_id' => $dados['vendedor_id'] ?? null,
            'operadora' => $dados['operadora'] ?? null,
            'data_inicio' => $dados['data_inicio'] ?? null,
            'data_fim' => $dados['data_fim'] ?? null,
            'status' => $dados['status'] ?? null,
            'per_page' => $dados['per_page'] ?? null,
            'empresa_id' => $empresaId,
        ];
    }

    private function aplicarFiltroEmpresa($query)
    {
        $empresaId = $this->tenantId();

        $query->where('vendas.empresa_id', $empresaId);

        return $query;
    }

    private function getVendasTotais($filtros, $perPage = null)
    {
        $empresaId = (int) $this->tenantId();
        $query = VendasModel::with([
            'user' => function ($query) use ($empresaId) {
                $query->select('id', 'name', 'empresa_id')
                    ->where('empresa_id', $empresaId);
            },
            'tabulacao',
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
            $query->where('vendas.tabulacao_id', $filtros['status']);
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
            DB::raw("SUM(valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END) as valor_total"),
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
        $empresaId = $this->tenantId();

        $query = VendasModel::select(
            'users.name as vendedor',
            DB::raw('COUNT(*) as total_vendas'),
            // valor_total agrega valor_contrato + angariação (mesmo critério do dashboard).
            DB::raw("SUM(vendas.valor_contrato + CASE WHEN vendas.angariacao_status = 'SIM' THEN COALESCE(vendas.angariacao_valor, 0) ELSE 0 END) as valor_total"),
            DB::raw("SUM(CASE WHEN vendas.angariacao_status = 'SIM' THEN COALESCE(vendas.angariacao_valor, 0) ELSE 0 END) as valor_angariacao"),
            DB::raw('SUM(vidas) as total_vidas')
        )->join('users', function ($join) {
            $join->on('vendas.user_id', '=', 'users.id')
                ->on('vendas.empresa_id', '=', 'users.empresa_id')
                ->where('users.is_platform_admin', false);
        })
            ->where('vendas.empresa_id', $empresaId)
            ->where('users.empresa_id', $empresaId);

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
            DB::raw("SUM(valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END) as valor_total"),
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
            DB::raw("SUM(valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END) as valor_total")
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
        $empresaId = $this->tenantId();

        $query = VendasModel::select(
            DB::raw("COALESCE(NULLIF(TRIM(contatos.entidade), ''), 'NÃO INFORMADO') as entidade"),
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw("SUM(vendas.valor_contrato + CASE WHEN vendas.angariacao_status = 'SIM' THEN COALESCE(vendas.angariacao_valor, 0) ELSE 0 END) as valor_total"),
            DB::raw('SUM(vendas.vidas) as total_vidas')
        )
            ->join('contatos', function ($join) {
                $join->on('vendas.contato_id', '=', 'contatos.id')
                    ->on('vendas.empresa_id', '=', 'contatos.empresa_id');
            })
            ->join('users', function ($join) {
                $join->on('vendas.user_id', '=', 'users.id')
                    ->on('vendas.empresa_id', '=', 'users.empresa_id')
                    ->where('users.is_platform_admin', false);
            })
            ->where('vendas.empresa_id', $empresaId)
            ->where('users.empresa_id', $empresaId)
            ->where('contatos.empresa_id', $empresaId);

        // Aplicar filtro de status de venda
        $query->whereIn('vendas.tabulacao_id', $this->reportableSaleStatusIds());

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
        $queryImplantadas->where('vendas.tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO));

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
        $empresaId = $this->tenantId();

        return User::query()->tenantMember($empresaId)
            ->where('ativo', 'Y')
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
        return Tabulacoes::where('empresa_id', $this->tenantId())
            ->whereIn('id', $this->reportableSaleStatusIds())
            ->select('id', 'descricao')
            ->orderBy('descricao')
            ->get();
    }

    public function getSalesAnalytical(Request $request)
    {
        $dados = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
        ]);
        $month = $dados['month'] ?? null;
        $year = $dados['year'] ?? null;

        $sales = $this->repositoryVendas->getSalesAnalytical($this->tenantId(), $month, $year);

        return response()->json($sales);
    }

    private function aplicarFiltroStatusVenda($query)
    {
        return $query->whereIn('vendas.tabulacao_id', $this->reportableSaleStatusIds());
    }

    public function getResultsBroker(Request $request)
    {
        $dados = $request->validate([
            'data_inicio' => ['nullable', 'date_format:Y-m-d', 'required_with:data_fim'],
            'data_fim' => ['nullable', 'date_format:Y-m-d', 'required_with:data_inicio', 'after_or_equal:data_inicio'],
        ]);
        $dataInicio = $dados['data_inicio'] ?? null;
        $dataFim = $dados['data_fim'] ?? null;

        $vendasCadastradasMes = VendasModel::where('user_id', Auth::user()->id)
            ->where('empresa_id', $this->tenantId())
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->selectRaw("SUM(valor_contrato + CASE WHEN angariacao_status = 'SIM' THEN COALESCE(angariacao_valor, 0) ELSE 0 END) as total")
            ->value('total') ?? 0;

        $vendasImplantadasMes = DB::table('vendas as a')
            ->where('a.tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO))
            ->where('a.user_id', Auth::user()->id)
            ->where('a.empresa_id', $this->tenantId())
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('a.created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->selectRaw("SUM(a.valor_contrato + CASE WHEN a.angariacao_status = 'SIM' THEN COALESCE(a.angariacao_valor, 0) ELSE 0 END) as total")
            ->value('total') ?? 0;

        $quantidadeContatosMes = DB::table('contatos as a')
            ->leftJoin('contatos_corretores as b', function ($join) {
                $join->on('a.id', '=', 'b.contato_id')
                    ->on('a.empresa_id', '=', 'b.empresa_id');
            })
            ->where('b.user_id', auth()->user()->id)
            ->where('a.empresa_id', $this->tenantId())
            ->where('b.empresa_id', $this->tenantId())
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('a.created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->count();

        $quantidadeVendasMes = VendasModel::where('user_id', Auth::user()->id)
            ->where('empresa_id', $this->tenantId())
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->count();

        $conversao = $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);

        $vendas = DB::table('vendas as a')
            ->leftJoin('tabulacoes as c', function ($join) {
                $join->on('c.id', '=', 'a.tabulacao_id')
                    ->on('c.empresa_id', '=', 'a.empresa_id');
            })
            ->leftJoin('users as backoffice', function ($join) {
                $join->on('backoffice.id', '=', 'a.backoffice_id')
                    ->on('backoffice.empresa_id', '=', 'a.empresa_id')
                    ->where('backoffice.is_platform_admin', false);
            })
            ->leftJoinSub(
                DB::table('venda_documentos')
                    ->selectRaw('venda_id, COUNT(*) as documentos_count')
                    ->whereNull('deleted_at')
                    ->groupBy('venda_id'),
                'documentos_resumo',
                'documentos_resumo.venda_id',
                '=',
                'a.id'
            )
            ->where('a.user_id', auth()->user()->id)
            ->where('a.empresa_id', $this->tenantId())
            ->when($dataInicio && $dataFim, fn ($q) => $q->whereBetween('a.created_at', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']))
            ->select(
                'a.id',
                'a.nome_contrato',
                'c.descricao',
                'c.codigo',
                'a.valor_contrato',
                'a.angariacao_status',
                'a.angariacao_valor',
                'a.created_at',
                'a.motivo_pendencia',
                'a.path_boleto_disponivel',
                'a.tabulacao_id',
                'a.boas_vindas_enviado_em',
                'a.documentacao_status',
                DB::raw('COALESCE(documentos_resumo.documentos_count, 0) as documentos_count'),
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
            $empresaId = (int) $this->tenantId();
            $venda = VendasModel::query()
                ->with([
                    'user' => fn ($query) => $query->select('id', 'name')->tenantMember($empresaId),
                    'backoffice' => fn ($query) => $query->select('id', 'name')->tenantMember($empresaId),
                    'tabulacao:id,descricao,codigo',
                    'titulares' => fn ($query) => $query->with([
                        'plano:id,nome,acomodacao',
                        'operadoraAnterior:id,nome',
                        'dependentes.plano:id,nome,acomodacao',
                        'dependentes.operadoraAnterior:id,nome',
                    ])->orderBy('id'),
                ])
                ->whereKey($id)
                ->where('empresa_id', $empresaId)
                ->where('user_id', Auth::id())
                ->first();

            if (! $venda) {
                return response()->json(['error' => 'Venda não encontrada'], 404);
            }

            $dados = $venda->toArray();
            $dados['vendedor_nome'] = $venda->user?->name;
            $dados['backoffice_nome'] = $venda->backoffice?->name;
            $dados['descricao'] = $venda->tabulacao?->descricao;
            $dados['codigo'] = $venda->tabulacao?->codigo;

            return response()->json($dados);
        } catch (\Throwable $th) {
            Log::error('Falha ao buscar detalhes da venda do vendedor.', [
                'empresa_id' => $this->tenantId(),
                'venda_id' => $id,
                'exception' => $th,
            ]);

            return response()->json(['error' => 'Não foi possível buscar os detalhes da venda.'], 500);
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
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'vendas.user_id')
                    ->on('users.empresa_id', '=', 'vendas.empresa_id')
                    ->where('users.is_platform_admin', false);
            })
            ->leftJoin('users as backoffice', function ($join) {
                $join->on('backoffice.id', '=', 'vendas.backoffice_id')
                    ->on('backoffice.empresa_id', '=', 'vendas.empresa_id')
                    ->where('backoffice.is_platform_admin', false);
            })
            ->where('vendas.empresa_id', $this->tenantId())
            ->where('vendas.tabulacao_id', $this->tabulationId(TabulationCode::ESTORNO));

        if ($user->user_role_id === UserRole::VENDEDOR) {
            $query->where('vendas.user_id', $user->id);
        }

        $vendas = $query->orderBy('vendas.updated_at', 'desc')->get();

        // Anexa data e responsável do estorno via último registro de histórico para tabulacao_nova_id = ESTORNO.
        $vendaIds = $vendas->pluck('id')->all();
        $historicos = VendaHistorico::whereIn('venda_id', $vendaIds)
            ->where('empresa_id', $this->tenantId())
            ->where('tabulacao_nova_id', $this->tabulationId(TabulationCode::ESTORNO))
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
            ->where('empresa_id', $this->tenantId())
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
            (int) $venda->tabulacao_id === $this->tabulationId(TabulationCode::ESTORNO),
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
            'portabilidades.operadoraDestino',
            'portabilidades.planoDestino',
            'contatoCorretor',
        ]);

        $cliente = DB::table('contatos')
            ->where('id', $venda->contato_id)
            ->where('empresa_id', $this->tenantId())
            ->first();

        abort_if(! $cliente, 404, 'Contato não encontrado.');

        $operadoras = Operadora::where('status', 'Y')
            ->where('empresa_id', $this->tenantId())
            ->orderBy('nome')
            ->get(['id', 'nome', 'coparticipacao_formato', 'angariacao_padrao']);

        $planosPortabilidade = Plano::where('empresa_id', $this->tenantId())
            ->where('status', 'Y')
            ->orderBy('nome')
            ->get(['id', 'operadora_id', 'nome']);

        return view('content.pages.vendas.edit-estorno', [
            'venda' => $venda,
            'cliente' => $cliente,
            'operadoras' => $operadoras,
            'planosPortabilidade' => $planosPortabilidade,
        ]);
    }

    public function reenviarEstorno(Request $request, $id)
    {
        $venda = $this->gateEstorno((int) $id);
        $empresaId = (int) $this->tenantId();
        $operadoraConfigurada = Operadora::where('empresa_id', $empresaId)
            ->where('status', 'Y')
            ->find($request->integer('operadora_id'));
        $valoresCoparticipacao = $operadoraConfigurada?->valoresCoparticipacao() ?? ['Y', 'N'];

        // Mesmo conjunto de validações da nova proposta. Observação do reenvio é opcional —
        // alinhamentos podem acontecer por canais internos (telefone/whatsapp) e o vendedor
        // só precisa devolver a venda para o backoffice continuar a tratativa.
        $validated = $request->validate([
            'observacao_reenvio' => 'nullable|string|max:500',
            'nome_contrato' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string',
            'operadora_id' => [
                'required',
                'integer',
                Rule::exists('operadoras', 'id')
                    ->where('empresa_id', $this->tenantId())
                    ->where('status', 'Y'),
            ],
            'titulares' => 'required|array|min:1',
            'titulares.*.nome' => 'required|string|max:100',
            'titulares.*.plano_id' => [
                'required',
                'integer',
                Rule::exists('planos', 'id')
                    ->where('empresa_id', $empresaId)
                    ->where('operadora_id', $request->integer('operadora_id'))
                    ->where('status', 'Y'),
            ],
            'titulares.*.coparticipacao' => ['required', Rule::in($valoresCoparticipacao)],
            'titulares.*.dependentes' => ['sometimes', 'array'],
            'titulares.*.dependentes.*.plano_id' => [
                'nullable',
                'integer',
                Rule::exists('planos', 'id')
                    ->where('empresa_id', $empresaId)
                    ->where('operadora_id', $request->integer('operadora_id'))
                    ->where('status', 'Y'),
            ],
            'titulares.*.dependentes.*.coparticipacao' => ['nullable', Rule::in($valoresCoparticipacao)],
            'portabilidades' => 'nullable|array|max:50',
            'portabilidades.*.nome' => 'required|string|max:100',
            'portabilidades.*.operadora_destino_id' => [
                'required',
                'integer',
                Rule::exists('operadoras', 'id')
                    ->where('empresa_id', $this->tenantId())
                    ->where('status', 'Y'),
            ],
            'portabilidades.*.plano_destino_id' => [
                'required',
                'integer',
                Rule::exists('planos', 'id')
                    ->where('empresa_id', $this->tenantId())
                    ->where('status', 'Y'),
            ],
        ], [
            'nome_contrato.required' => 'Razão Social é obrigatória.',
            'cpf_cnpj.required' => 'CNPJ/CPF é obrigatório.',
            'operadora_id.required' => 'Selecione uma operadora.',
            'titulares.required' => 'Adicione pelo menos um titular.',
            'titulares.min' => 'Adicione pelo menos um titular.',
            'titulares.*.nome.required' => 'Nome do titular é obrigatório.',
            'titulares.*.plano_id.required' => 'Selecione o plano para todos os titulares.',
            'portabilidades.*.nome.required' => 'Informe o nome completo de cada cliente em portabilidade.',
            'portabilidades.*.operadora_destino_id.required' => 'Selecione a operadora de destino de cada portabilidade.',
            'portabilidades.*.plano_destino_id.required' => 'Selecione o plano de destino de cada portabilidade.',
        ]);

        foreach ($validated['portabilidades'] ?? [] as $index => $portabilidade) {
            $planoValido = Plano::whereKey((int) $portabilidade['plano_destino_id'])
                ->where('empresa_id', $this->tenantId())
                ->where('operadora_id', (int) $portabilidade['operadora_destino_id'])
                ->exists();

            if (! $planoValido) {
                throw ValidationException::withMessages([
                    "portabilidades.$index.plano_destino_id" => 'O plano selecionado não pertence à operadora de destino informada.',
                ]);
            }
        }

        $observacaoReenvio = trim((string) $request->input('observacao_reenvio')) ?: null;

        try {
            DB::beginTransaction();

            // 1) Atualiza a venda + filhos com o payload completo.
            $this->repositoryVendas->updateContractFull($venda->id, $request->all());

            // 2) Move status da VENDA: ESTORNO → VENDA + grava histórico.
            $ok = $this->repositoryVendas->alterStatusVenda(
                $venda->id,
                $this->tabulationId(TabulationCode::VENDA),
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
            report($th);

            return redirect()->back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Não foi possível reenviar a venda neste momento.');
        }

        // 4) Notifica o backoffice dono.
        if ($venda->backoffice_id) {
            $bo = User::query()
                ->tenantMember($this->tenantId())
                ->find($venda->backoffice_id);
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

    private function tabulationId(string $code): int
    {
        return $this->tabulationCatalog->id((int) $this->tenantId(), $code);
    }

    private function reportableSaleStatusIds(): array
    {
        $codes = [...TabulationCode::POS_VENDA_ELEGIVEIS, TabulationCode::ESTORNO];

        return array_values($this->tabulationCatalog->requiredIds(
            (int) $this->tenantId(),
            $codes
        ));
    }
}
