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

        $validTabulations = $this->getValidTabulations();

        $valorSql = "a.valor_contrato + CASE WHEN a.angariacao_status = 'SIM' THEN COALESCE(a.angariacao_valor, 0) ELSE 0 END";
        $sumValorSql = "SUM($valorSql)";

        // Vendas Cadastradas (valor)
        $salesRegistered = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->selectRaw("$sumValorSql as total")
            ->value('total') ?? 0;

        // Vendas Implantadas (valor)
        $salesImplanted = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('c.tabulacao_id', Tabulations::IMPLANTADO)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->selectRaw("$sumValorSql as total")
            ->value('total') ?? 0;

        // Quantidade de vendas cadastradas (usado para ticket médio)
        $qtyRegistered = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->count();

        // Ticket médio
        $ticketMedio = $qtyRegistered > 0 ? $salesRegistered / $qtyRegistered : 0;

        // Operadora mais vendida (ano inteiro)
        $operadoraMaisVendida = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->select('a.operadora', DB::raw('COUNT(*) as total'))
            ->whereYear('a.created_at', $year)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->whereNotNull('a.operadora')
            ->where('a.operadora', '!=', '')
            ->groupBy('a.operadora')
            ->orderByDesc('total')
            ->first();

        // Taxa de conversão
        $leadsTrabalhados = DB::table('contatos_corretores')
            ->where('user_id', $user->id)
            ->where('empresa_id', $empresaId)
            ->distinct('contato_id')
            ->count('contato_id');

        $vendasCount = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->whereYear('a.created_at', $year)
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->count();

        $taxaConversao = $leadsTrabalhados > 0 ? round(($vendasCount / $leadsTrabalhados) * 100, 1) : 0;

        // Evolução mensal
        $monthlyOverview = DB::table('vendas as a')
            ->select(
                DB::raw('MONTH(a.created_at) as month'),
                DB::raw("$sumValorSql as total")
            )
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->where('a.user_id', $user->id)
            ->where('a.empresa_id', $empresaId)
            ->whereYear('a.created_at', $year)
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->groupBy(DB::raw('MONTH(a.created_at)'))
            ->orderBy('month')
            ->get();

        // Ranking mensal e trimestral (não segue filtro de seleção, sempre mês/trimestre vigente)
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;
        $currentQuarter = $now->quarter;
        $quarterStartMonth = ($currentQuarter - 1) * 3 + 1;
        $quarterEndMonth = $currentQuarter * 3;

        // Ranking mensal - todos vendedores da empresa
        $rankingMensal = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->select('a.user_id', DB::raw("$sumValorSql as total_vendas"))
            ->where('a.empresa_id', $empresaId)
            ->whereYear('a.created_at', $currentYear)
            ->whereMonth('a.created_at', $currentMonth)
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->groupBy('a.user_id')
            ->orderByDesc('total_vendas')
            ->get();

        $posicaoMensal = null;
        $totalVendedoresMes = $rankingMensal->count();
        foreach ($rankingMensal->values() as $index => $item) {
            if ($item->user_id == $user->id) {
                $posicaoMensal = $index + 1;
                break;
            }
        }

        // Ranking trimestral - todos vendedores da empresa
        $rankingTrimestral = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->select('a.user_id', DB::raw("$sumValorSql as total_vendas"))
            ->where('a.empresa_id', $empresaId)
            ->whereYear('a.created_at', $currentYear)
            ->whereRaw('MONTH(a.created_at) BETWEEN ? AND ?', [$quarterStartMonth, $quarterEndMonth])
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->groupBy('a.user_id')
            ->orderByDesc('total_vendas')
            ->get();

        $posicaoTrimestral = null;
        $totalVendedoresTrimestre = $rankingTrimestral->count();
        foreach ($rankingTrimestral->values() as $index => $item) {
            if ($item->user_id == $user->id) {
                $posicaoTrimestral = $index + 1;
                break;
            }
        }

        // Vendas recentes (últimas 10)
        $vendasRecentes = DB::table('vendas as a')
            ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
            ->leftJoin('tabulacoes as t', 't.id', '=', 'c.tabulacao_id')
            ->select(
                'a.nome_contrato',
                'a.operadora',
                'a.nome_plano',
                'a.valor_contrato',
                'a.angariacao_status',
                'a.angariacao_valor',
                'a.created_at',
                't.descricao as status'
            )
            ->where('a.empresa_id', $empresaId)
            ->where('a.user_id', $user->id)
            ->whereIn('c.tabulacao_id', $validTabulations)
            ->orderByDesc('a.created_at')
            ->limit(10)
            ->get()
            ->map(function ($venda) {
                $createdAt = Carbon::parse($venda->created_at);
                $valor = (float) $venda->valor_contrato;
                if ($venda->angariacao_status === 'SIM') {
                    $valor += (float) ($venda->angariacao_valor ?? 0);
                }
                $venda->valor_total = $valor;
                $venda->created_at = $createdAt->format('d/m/Y');
                return $venda;
            });

        $data = [
            'sales_registered' => (float) $salesRegistered,
            'sales_implanted' => (float) $salesImplanted,
            'ticket_medio' => round((float) $ticketMedio, 2),
            'operadora_mais_vendida' => $operadoraMaisVendida ? [
                'operadora' => $operadoraMaisVendida->operadora,
                'total' => $operadoraMaisVendida->total,
            ] : null,
            'taxa_conversao' => [
                'leads_trabalhados' => $leadsTrabalhados,
                'vendas' => $vendasCount,
                'percentual' => $taxaConversao,
            ],
            'ranking_mensal' => [
                'posicao' => $posicaoMensal,
                'total_vendedores' => $totalVendedoresMes,
                'mes' => $currentMonth,
            ],
            'ranking_trimestral' => [
                'posicao' => $posicaoTrimestral,
                'total_vendedores' => $totalVendedoresTrimestre,
                'trimestre' => $currentQuarter,
            ],
            'monthly_overview' => $monthlyOverview,
            'vendas_recentes' => $vendasRecentes,
        ];

        return response()->json($data);
    }

    private function getValidTabulations(): array
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
}
