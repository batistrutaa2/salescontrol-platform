<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use Illuminate\Support\Facades\DB;
use App\Enums\Tabulations;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    protected $vendasRepository;
    protected $contatosRepository;

    public function __construct(
        VendasRepositoryInterface $vendasRepositoryInterface,
        ContatosRepositoryInterface $contatosRepositoryInterface
    ) {
        $this->vendasRepository = $vendasRepositoryInterface;
        $this->contatosRepository = $contatosRepositoryInterface;
    }

    public function index()
    {
        $user = Auth::user();
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $empresaId = $user->empresa_id;

        // Fetch data for the dashboard
        // We need to ensure these repository methods exist or create similar logic if they are specific to admin
        // Assuming we can filter by user_id for the salesperson

        // Sales per month (Registered vs Implanted)
        // We might need to adjust repository methods to filter by specific user if they don't already
        // For now, I'll assume we can use existing ones or I'll check the repository to see if I need to pass user_id
        
        // Let's check VendasRepository first to see if we can filter by user. 
        // The HomePage controller uses:
        // $this->vendasRepository->quantidadeVendasCadastradasPorVendedor($month, $year, $empresaId);
        // This seems to return a list of all sellers. We need just for the logged in user.
        
        // Actually, let's look at the repository implementation in a moment. 
        // For now I will scaffold the controller and then refine the data fetching.

        return view('content.pages.dashboard-vendedor');
    }

    public function getMetrics(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $user = Auth::user();
        $empresaId = $user->empresa_id;

        // 1. Sales Registered (Cadastradas)
        // Reusing logic from VendasRepository but filtering by user
        // 1. Sales Registered (Cadastradas)
        // Reusing logic from VendasRepository but filtering by user
        $salesRegistered = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->whereIn('c.tabulacao_id', [
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
            ->sum('a.valor_contrato');

        // 2. Sales Implanted (Implantadas)
        $salesImplanted = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('c.tabulacao_id', Tabulations::IMPLANTADO)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->sum('a.valor_contrato');

        // 3. Leads Count by Status (Tabulation)
        // We need to check ContatosRepository for similar logic or build it here
        // Assuming 'contatos_corretores' links contacts to users (brokers)
        $leadsStatus = DB::table('contatos_corretores as cc')
            ->select('t.descricao as tabulacao', DB::raw('count(*) as total'))
            ->join('tabulacoes as t', 't.id', '=', 'cc.tabulacao_id')
            ->where('cc.user_id', $user->id)
            ->where('t.tipo_tabulacao', 'C')
            // Maybe filter by date? The requirement says "quantos leads ele tem em cada status da fila", implying current state, not monthly.
            // But "overview geral dos meses dele" might imply history. 
            // Usually "status da fila" means current active leads.
            ->groupBy('t.descricao')
            ->get();

        // 4. Overview (Monthly history?)
        // "um overview geral dos meses dele"
        // Let's get sales for the last 6 months
        $monthlyOverview = DB::table('vendas as a')
            ->select(DB::raw('MONTH(a.created_at) as month'), DB::raw('SUM(a.valor_contrato) as total'))
            ->where('a.user_id', $user->id)
            ->where('a.empresa_id', $empresaId)
            ->whereYear('a.created_at', $year) // Current year for now
            ->groupBy(DB::raw('MONTH(a.created_at)'))
            ->orderBy('month')
            ->get();

        $data = [
            'sales_registered' => $salesRegistered,
            'sales_implanted' => $salesImplanted,
            'leads_status' => $leadsStatus,
            'monthly_overview' => $monthlyOverview
        ];

        return response()->json($data);
    }
}
