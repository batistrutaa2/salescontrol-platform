<?php

namespace App\Http\Controllers\pages\vendas;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Vendas as VendasModel;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Enums\Tabulations;

class Vendas extends Controller
{

    protected VendasRepository $repositoryVendas;
    protected UsuariosRepository $usuariosRepository;
    public function __construct(
        VendasRepositoryInterface $vendasRepositoryInterface,
        UsuariosRepositoryInterface $usuariosRepositoryInterface
    ) {

        $this->repositoryVendas = $vendasRepositoryInterface;
        $this->usuariosRepository = $usuariosRepositoryInterface;
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
                    return response()->json(["error" => true]);
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
                "error" => false
            ];

            return response()->json($response);
        } catch (\Throwable $th) {
            return response()->json(["error" => true]);
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
                'resumo_geral' => $this->getResumoGeral($filtros),
                'vendedores' => $this->getVendedoresPorEmpresa(),
                'anos_disponiveis' => $this->getAnosDisponiveis($filtros),
                'operadoras' => $this->getOperadoras($filtros)
            ]
        ]);
    }

    public function listarVendas(Request $request)
    {
        $filtros = $this->aplicarFiltros($request);
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
                'to' => $vendas->lastItem()
            ]
        ]);
    }

    public function exportar(Request $request)
    {
        $filtros = $this->aplicarFiltros($request);
        $vendas = $this->getVendasTotais($filtros);

        return response()->json([
            'success' => true,
            'message' => 'Exportação em desenvolvimento',
            'total_registros' => $vendas->count()
        ]);
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
            'empresa_id' => Auth::user()->empresa_id // Filtro automático por empresa
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
            }
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
            DB::raw('MONTH(data_vigencia) as mes'),
            DB::raw('YEAR(data_vigencia) as ano'),
            DB::raw('COUNT(*) as total_vendas'),
            DB::raw('SUM(valor_contrato) as valor_total'),
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
            DB::raw('SUM(valor_contrato) as valor_total'),
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
            DB::raw('SUM(valor_contrato) as valor_total'),
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
            DB::raw('SUM(valor_contrato) as valor_total')
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

        return [
            'total_contratos' => $query->count(),
            'valor_total' => $query->sum('valor_contrato') ?? 0,
            'total_vidas' => $query->sum('vidas') ?? 0,
            'ticket_medio' => $query->avg('valor_contrato') ?? 0,
            'vidas_por_contrato' => $query->avg('vidas') ?? 0
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
                Tabulations::ANALISE_DOCUMENTOS
            ]);
        });
    }

    public function getResultsBroker(Request $request)
    {
        $vendasCadastradasMes = VendasModel::where('user_id', Auth::user()->id)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->whereMonth('created_at', $request->mes)
            ->whereYear('created_at', $request->ano)
            ->sum('valor_contrato');

        $vendasImplantadasMes = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
            ->where('b.tabulacao_id', Tabulations::IMPLANTADO)
            ->where('a.user_id', Auth::user()->id)
            ->whereMonth('a.created_at', $request->mes)
            ->whereYear('a.created_at', $request->ano)
            ->sum('a.valor_contrato');



        $quantidadeContatosMes = DB::table('contatos as a')
            ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
            ->where('b.user_id', auth()->user()->id)
            ->where('b.empresa_id', Auth::user()->empresa_id)
            ->whereMonth('a.created_at', $request->mes)
            ->whereYear('a.created_at', $request->ano)
            ->count();

        $quantidadeVendasMes = VendasModel::where('user_id', Auth::user()->id)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->whereMonth('created_at', $request->mes)
            ->whereYear('created_at', $request->ano)
            ->count();

        $conversao = $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);

        $vendas = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as b', 'a.contato_id', '=', 'b.contato_id')
            ->leftJoin('tabulacoes as c', 'c.id', '=', 'b.tabulacao_id')
            ->where('a.user_id', auth()->user()->id)
            ->whereMonth('a.created_at', $request->mes)
            ->whereYear('a.created_at', $request->ano)
            ->select('a.id', 'a.nome_contrato', 'c.descricao', 'a.valor_contrato', 'a.motivo_pendencia')
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

            return "%" . number_format($conversao, 2, ',', '.');
        } catch (\Throwable $th) {
            return 0.0;
        }
    }

}
