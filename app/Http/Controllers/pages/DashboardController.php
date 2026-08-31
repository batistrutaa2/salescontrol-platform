<?php

namespace App\Http\Controllers\pages;

use App\Enums\Tabulations;
use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('content.pages.dashboard-vendedor');
    }

    public function getMetrics(Request $request)
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:year,month,quarter'],
        ]);

        $period = $validated['period'] ?? 'year';
        $now = now();
        $year = $now->year;
        $quarterStartMonth = ($now->quarter - 1) * 3 + 1;
        [$startMonth, $endMonth] = match ($period) {
            'month' => [$now->month, $now->month],
            'quarter' => [$quarterStartMonth, $quarterStartMonth + 2],
            default => [1, 12],
        };
        $user = Auth::user();
        $empresaId = (int) $user->empresa_id;

        $sales = $this->validSalesQuery($empresaId, $year)
            ->whereRaw('MONTH(v.created_at) BETWEEN ? AND ?', [$startMonth, $endMonth])
            ->where('v.user_id', $user->id)
            ->leftJoin('tabulacoes as t', 't.id', '=', 'v.tabulacao_id')
            ->select([
                'v.id', 'v.nome_contrato', 'v.operadora', 'v.nome_plano',
                'v.valor_contrato', 'v.angariacao_status', 'v.angariacao_valor',
                'v.tabulacao_id', 'v.created_at', 't.descricao as status',
            ])
            ->orderByDesc('v.created_at')
            ->orderByDesc('v.id')
            ->get()
            ->map(function ($sale) {
                $sale->valor_contrato = (float) ($sale->valor_contrato ?? 0);
                $sale->angariacao = $sale->angariacao_status === 'SIM'
                    ? (float) ($sale->angariacao_valor ?? 0)
                    : 0.0;
                $sale->valor_total = $sale->valor_contrato + $sale->angariacao;
                $sale->month = Carbon::parse($sale->created_at)->month;

                return $sale;
            });

        $totalValid = (float) $sales->sum('valor_total');
        $totalContracts = (float) $sales->sum('valor_contrato');
        $totalFundraising = (float) $sales->sum('angariacao');
        $salesCount = $sales->count();
        $implanted = $sales->where('tabulacao_id', Tabulations::IMPLANTADO);
        $reversed = $sales->where('tabulacao_id', Tabulations::ESTORNO);

        $monthly = collect(range($startMonth, $endMonth))->map(function (int $month) use ($sales) {
            $monthSales = $sales->where('month', $month);

            return [
                'month' => $month,
                'count' => $monthSales->count(),
                'total' => round((float) $monthSales->sum('valor_total'), 2),
                'contracts' => round((float) $monthSales->sum('valor_contrato'), 2),
                'fundraising' => round((float) $monthSales->sum('angariacao'), 2),
            ];
        })->values();

        $bestMonth = $monthly->sort(function (array $a, array $b) {
            return [$b['total'], $b['count']] <=> [$a['total'], $a['count']];
        })->first(fn (array $month) => $month['count'] > 0);

        $topProduct = $sales
            ->filter(fn ($sale) => filled(trim((string) $sale->nome_plano)))
            ->groupBy(fn ($sale) => mb_strtolower(trim((string) $sale->nome_plano)))
            ->map(function (Collection $productSales) {
                $first = $productSales->first();
                $operators = $productSales
                    ->filter(fn ($sale) => filled(trim((string) $sale->operadora)))
                    ->groupBy(fn ($sale) => mb_strtolower(trim((string) $sale->operadora)))
                    ->map(function (Collection $operatorSales) {
                        return [
                            'name' => trim((string) $operatorSales->first()->operadora),
                            'count' => $operatorSales->count(),
                            'total' => (float) $operatorSales->sum('valor_total'),
                        ];
                    })
                    ->sort(function (array $a, array $b) {
                        return [$b['count'], $b['total']] <=> [$a['count'], $a['total']];
                    })
                    ->values();
                $leadingOperator = $operators->first();
                $otherOperators = max(0, $operators->count() - 1);

                return [
                    'name' => trim((string) $first->nome_plano),
                    'operator' => $leadingOperator
                        ? $leadingOperator['name'].($otherOperators > 0 ? ' + '.$otherOperators.' '.($otherOperators === 1 ? 'operadora' : 'operadoras') : '')
                        : '',
                    'count' => $productSales->count(),
                    'total' => round((float) $productSales->sum('valor_total'), 2),
                ];
            })
            ->sort(function (array $a, array $b) {
                return [$b['count'], $b['total']] <=> [$a['count'], $a['total']];
            })
            ->first();

        $largestSale = $sales->sortByDesc('valor_total')->first();
        $ranking = $this->annualRanking(
            $empresaId,
            $year,
            (int) $user->id,
            (bool) $user->excluir_ranking,
            $startMonth,
            $endMonth
        );

        $detailSales = $sales;

        $detailTotal = $detailSales->count();
        $detailSales = $detailSales->take(12)->map(fn ($sale) => [
            'id' => (int) $sale->id,
            'date' => Carbon::parse($sale->created_at)->format('d/m/Y'),
            'client' => $sale->nome_contrato ?: 'Contrato sem nome',
            'operator' => $sale->operadora ?: 'Não informada',
            'product' => $sale->nome_plano ?: 'Não informado',
            'contract_value' => $sale->valor_contrato,
            'fundraising' => $sale->angariacao,
            'total' => $sale->valor_total,
            'status' => $sale->status ?: 'Sem status',
        ])->values();

        return response()->json([
            'year' => $year,
            'period' => $period,
            'period_label' => match ($period) {
                'month' => 'mês atual',
                'quarter' => $now->quarter.'º trimestre',
                default => 'ano atual',
            },
            'period_key' => match ($period) {
                'month' => $now->format('Y-m'),
                'quarter' => $year.'-Q'.$now->quarter,
                default => (string) $year,
            },
            'annual' => [
                'valid_value' => round($totalValid, 2),
                'contract_value' => round($totalContracts, 2),
                'fundraising_value' => round($totalFundraising, 2),
                'sales_count' => $salesCount,
                'average_ticket' => $salesCount > 0 ? round($totalValid / $salesCount, 2) : 0.0,
                'implanted_count' => $implanted->count(),
                'implanted_value' => round((float) $implanted->sum('valor_total'), 2),
                'implantation_rate' => $salesCount > 0 ? round($implanted->count() / $salesCount * 100, 1) : 0.0,
                'reversed_count' => $reversed->count(),
            ],
            'ranking' => $ranking,
            'top_product' => $topProduct,
            'largest_sale' => $largestSale ? [
                'id' => (int) $largestSale->id,
                'client' => $largestSale->nome_contrato ?: 'Contrato sem nome',
                'operator' => $largestSale->operadora ?: 'Não informada',
                'product' => $largestSale->nome_plano ?: 'Não informado',
                'date' => Carbon::parse($largestSale->created_at)->format('d/m/Y'),
                'contract_value' => $largestSale->valor_contrato,
                'fundraising' => $largestSale->angariacao,
                'total' => $largestSale->valor_total,
            ] : null,
            'best_month' => $bestMonth,
            'monthly' => $monthly,
            'detail_sales' => $detailSales,
            'detail_total' => $detailTotal,
            'pending_reversals' => DB::table('vendas')
                ->where('empresa_id', $empresaId)
                ->where('user_id', $user->id)
                ->whereYear('created_at', $year)
                ->whereRaw('MONTH(created_at) BETWEEN ? AND ?', [$startMonth, $endMonth])
                ->where('tabulacao_id', Tabulations::ESTORNO)
                ->count(),
        ]);
    }

    private function validSalesQuery(int $empresaId, int $year): Builder
    {
        return DB::table('vendas as v')
            ->where('v.empresa_id', $empresaId)
            ->whereYear('v.created_at', $year)
            ->where(function (Builder $status) {
                $status->whereNull('v.tabulacao_id')
                    ->orWhere('v.tabulacao_id', '!=', Tabulations::DECLINIO);
            });
    }

    private function annualRanking(
        int $empresaId,
        int $year,
        int $userId,
        bool $excluded,
        ?int $startMonth = null,
        ?int $endMonth = null
    ): array {
        $valueSql = "COALESCE(v.valor_contrato, 0) + CASE WHEN v.angariacao_status = 'SIM' THEN COALESCE(v.angariacao_valor, 0) ELSE 0 END";
        $rows = $this->validSalesQuery($empresaId, $year)
            ->when($startMonth, fn (Builder $query) => $query->whereRaw('MONTH(v.created_at) BETWEEN ? AND ?', [$startMonth, $endMonth]))
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->where(function (Builder $query) {
                $query->whereNull('u.excluir_ranking')->orWhere('u.excluir_ranking', false);
            })
            ->select('u.id', 'u.name')
            ->selectRaw("SUM($valueSql) as valid_value")
            ->selectRaw('SUM(CASE WHEN v.tabulacao_id = ? THEN '.$valueSql.' ELSE 0 END) as implanted_value', [Tabulations::IMPLANTADO])
            ->groupBy('u.id', 'u.name')
            ->get()
            ->sort(function ($a, $b) {
                $value = (float) $b->valid_value <=> (float) $a->valid_value;
                if ($value !== 0) {
                    return $value;
                }

                $implanted = (float) $b->implanted_value <=> (float) $a->implanted_value;

                return $implanted !== 0 ? $implanted : strcasecmp($a->name, $b->name);
            })
            ->values();

        $position = null;
        if (! $excluded) {
            $found = $rows->search(fn ($row) => (int) $row->id === $userId);
            $position = $found === false ? null : $found + 1;
        }

        $current = $position ? $rows->get($position - 1) : null;
        $previous = $position && $position > 1 ? $rows->get($position - 2) : null;
        $leaders = $rows->take(3)->map(fn ($row, $index) => [
            'position' => $index + 1,
            'seller' => $row->name,
            'is_current_user' => (int) $row->id === $userId,
        ])->values();

        if ($position && $position > 3 && $current) {
            $leaders->push([
                'position' => $position,
                'seller' => $current->name,
                'is_current_user' => true,
            ]);
        }

        return [
            'viewer_id' => $userId,
            'position' => $position,
            'total_sellers' => $rows->count(),
            'excluded' => $excluded,
            'valid_value' => $current ? round((float) $current->valid_value, 2) : 0.0,
            'distance_to_previous' => $current && $previous
                ? round((float) $previous->valid_value - (float) $current->valid_value, 2)
                : 0.0,
            'previous_position' => $position && $position > 1 ? $position - 1 : null,
            'leaders' => $leaders,
        ];
    }
}
