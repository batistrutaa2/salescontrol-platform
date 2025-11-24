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
        return view('content.pages.dashboard-vendedor');
    }

    public function getMetrics(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $user = Auth::user();
        $empresaId = $user->empresa_id;

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

        $salesImplanted = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('c.tabulacao_id', Tabulations::IMPLANTADO)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->sum('a.valor_contrato');

        $leadsStatus = DB::table('contatos_corretores as cc')
            ->select('t.descricao as tabulacao', DB::raw('count(*) as total'))
            ->join('tabulacoes as t', 't.id', '=', 'cc.tabulacao_id')
            ->where('cc.user_id', $user->id)
            ->where('t.tipo_tabulacao', 'C')

            ->groupBy('t.descricao')
            ->get();

        $monthlyOverview = DB::table('vendas as a')
            ->select(DB::raw('MONTH(a.created_at) as month'), DB::raw('SUM(a.valor_contrato) as total'))
            ->where('a.user_id', $user->id)
            ->where('a.empresa_id', $empresaId)
            ->whereYear('a.created_at', $year) 
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
