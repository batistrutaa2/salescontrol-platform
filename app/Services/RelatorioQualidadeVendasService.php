<?php

namespace App\Services;

use App\Enums\Tabulations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RelatorioQualidadeVendasService
{
    public const IMPLANTADA = 'implantada';

    public const EM_PROCESSO = 'em_processo';

    public const ESTORNO = 'estorno';

    public const DECLINIO = 'declinio';

    public function resumo(int $empresaId, CarbonImmutable $inicio, CarbonImmutable $fim, ?int $vendedorId = null): array
    {
        $linhas = $this->queryBase($empresaId, $inicio, $fim, $vendedorId)->get();
        $atual = $this->agregar($linhas, $inicio, $fim);

        $dias = $inicio->diffInDays($fim) + 1;
        $comparacaoFim = $inicio->subDay();
        $comparacaoInicio = $comparacaoFim->subDays($dias - 1);
        $comparacao = null;

        if ($comparacaoInicio->year === $inicio->year) {
            $linhasAnteriores = $this->queryBase($empresaId, $comparacaoInicio, $comparacaoFim, $vendedorId)->get();
            $comparacao = [
                'periodo' => $this->periodo($comparacaoInicio, $comparacaoFim),
                'kpis' => $this->agregar($linhasAnteriores, $comparacaoInicio, $comparacaoFim)['kpis'],
            ];
        }

        return array_merge($atual, [
            'periodo' => $this->periodo($inicio, $fim),
            'comparacao' => $comparacao,
            'vendedores_filtro' => $this->vendedoresDoAno($empresaId, $inicio->year),
        ]);
    }

    public function detalhes(
        int $empresaId,
        CarbonImmutable $inicio,
        CarbonImmutable $fim,
        ?int $vendedorId,
        ?string $categoria,
        int $porPagina = 20
    ): LengthAwarePaginator {
        $query = $this->queryPropostas($empresaId, $inicio, $fim, $vendedorId);

        $this->aplicarCategoria($query, $categoria);

        return $query->orderByDesc('v.created_at')->orderByDesc('v.id')->paginate($porPagina)
            ->through(fn ($venda) => $this->normalizarProposta($venda));
    }

    private function queryPropostas(int $empresaId, CarbonImmutable $inicio, CarbonImmutable $fim, ?int $vendedorId): Builder
    {
        return $this->queryBase($empresaId, $inicio, $fim, $vendedorId)
            ->select([
                'v.id', 'v.numero_proposta', 'v.nome_contrato', 'v.created_at',
                'v.data_implantacao', 'v.valor_contrato', 'v.angariacao_status',
                'v.angariacao_valor', 'v.tabulacao_id', 't.descricao as status',
                'u.id as vendedor_id', 'u.name as vendedor',
            ])
            ->selectRaw($this->valorSql().' as valor_total');
    }

    private function normalizarProposta(object $venda): array
    {
        return [
            'id' => $venda->id,
            'numero_proposta' => $venda->numero_proposta,
            'cliente' => $venda->nome_contrato,
            'vendedor_id' => $venda->vendedor_id,
            'vendedor' => $venda->vendedor,
            'data_venda' => CarbonImmutable::parse($venda->created_at)->format('d/m/Y'),
            'data_implantacao' => $venda->data_implantacao
                ? CarbonImmutable::parse($venda->data_implantacao)->format('d/m/Y')
                : null,
            'status' => $venda->status ?: 'Sem status',
            'categoria' => $this->categoria((int) $venda->tabulacao_id),
            'valor_contrato' => (float) ($venda->valor_contrato ?? 0),
            'angariacao' => $venda->angariacao_status === 'SIM' ? (float) ($venda->angariacao_valor ?? 0) : 0.0,
            'valor_total' => (float) $venda->valor_total,
        ];
    }

    private function queryBase(int $empresaId, CarbonImmutable $inicio, CarbonImmutable $fim, ?int $vendedorId = null): Builder
    {
        return DB::table('vendas as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->leftJoin('tabulacoes as t', 't.id', '=', 'v.tabulacao_id')
            ->select([
                'v.id', 'v.user_id', 'v.tabulacao_id', 'v.created_at',
                'v.valor_contrato', 'v.angariacao_status', 'v.angariacao_valor',
                'u.name as vendedor', 'u.excluir_ranking',
            ])
            ->where('v.empresa_id', $empresaId)
            ->whereBetween('v.created_at', [$inicio->startOfDay(), $fim->endOfDay()])
            ->when($vendedorId, fn (Builder $query) => $query->where('v.user_id', $vendedorId));
    }

    private function agregar(Collection $linhas, CarbonImmutable $inicio, CarbonImmutable $fim): array
    {
        $normalizadas = $linhas->map(function ($linha) {
            $linha->categoria_relatorio = $this->categoria((int) $linha->tabulacao_id);
            $linha->valor_calculado = (float) ($linha->valor_contrato ?? 0)
                + ($linha->angariacao_status === 'SIM' ? (float) ($linha->angariacao_valor ?? 0) : 0);

            return $linha;
        });

        $kpis = $this->metricas($normalizadas);
        $porVendedor = $normalizadas->groupBy('user_id')->map(function (Collection $vendas) {
            $primeira = $vendas->first();

            return array_merge([
                'vendedor_id' => (int) $primeira->user_id,
                'vendedor' => $primeira->vendedor,
                'excluir_ranking' => (bool) $primeira->excluir_ranking,
            ], $this->metricas($vendas));
        })->values();

        $elegiveis = $porVendedor->reject(fn ($item) => $item['excluir_ranking']);
        $rankings = [
            'valor_valido' => $this->ranquear($elegiveis, ['valor_valido', 'valor_implantado']),
            'percentual_implantacao' => $this->ranquear($elegiveis, ['percentual_implantacao', 'elegiveis', 'valor_implantado']),
            'valor_implantado' => $this->ranquear($elegiveis, ['valor_implantado', 'implantadas']),
            'perdas' => $this->ranquear($elegiveis, ['percentual_perda', 'valor_perdido', 'perdidas']),
        ];

        $posicoes = collect($rankings['valor_valido'])->pluck('posicao', 'vendedor_id');
        $vendedores = $porVendedor->map(function ($item) use ($posicoes) {
            $item['posicao_geral'] = $item['excluir_ranking'] ? null : $posicoes->get($item['vendedor_id']);

            return $item;
        })->sortBy(fn ($item) => $item['posicao_geral'] ?? PHP_INT_MAX)->values()->all();

        $agrupadasPorMes = $normalizadas->groupBy(fn ($venda) => CarbonImmutable::parse($venda->created_at)->format('Y-m'));
        $mensal = collect();
        $cursor = $inicio->startOfMonth();
        while ($cursor <= $fim->startOfMonth()) {
            $chave = $cursor->format('Y-m');
            $mensal->push(array_merge([
                'mes' => $chave,
                'label' => $cursor->locale('pt_BR')->translatedFormat('M'),
            ], $this->metricas($agrupadasPorMes->get($chave, collect()))));
            $cursor = $cursor->addMonth();
        }
        $mensal = $mensal->all();

        return compact('kpis', 'vendedores', 'rankings', 'mensal');
    }

    private function metricas(Collection $vendas): array
    {
        $porCategoria = fn (string $categoria) => $vendas->where('categoria_relatorio', $categoria);
        $implantadas = $porCategoria(self::IMPLANTADA);
        $processo = $porCategoria(self::EM_PROCESSO);
        $estornos = $porCategoria(self::ESTORNO);
        $declinios = $porCategoria(self::DECLINIO);
        $elegiveis = $implantadas->count() + $processo->count();
        $valorElegivel = $implantadas->sum('valor_calculado') + $processo->sum('valor_calculado');
        $perdidas = $estornos->count() + $declinios->count();
        $valorPerdido = $estornos->sum('valor_calculado') + $declinios->sum('valor_calculado');
        $total = $vendas->count();

        return [
            'total_propostas' => $total,
            'valor_bruto' => round((float) $vendas->sum('valor_calculado'), 2),
            'valor_contratos' => round((float) $vendas->sum(fn ($v) => (float) ($v->valor_contrato ?? 0)), 2),
            'valor_angariacao' => round((float) $vendas->sum(fn ($v) => $v->angariacao_status === 'SIM' ? (float) ($v->angariacao_valor ?? 0) : 0), 2),
            'valor_valido' => round((float) $vendas->where('categoria_relatorio', '!=', self::DECLINIO)->sum('valor_calculado'), 2),
            'implantadas' => $implantadas->count(),
            'valor_implantado' => round((float) $implantadas->sum('valor_calculado'), 2),
            'em_processo' => $processo->count(),
            'valor_em_processo' => round((float) $processo->sum('valor_calculado'), 2),
            'estornos' => $estornos->count(),
            'valor_estornado' => round((float) $estornos->sum('valor_calculado'), 2),
            'declinios' => $declinios->count(),
            'valor_declinado' => round((float) $declinios->sum('valor_calculado'), 2),
            'elegiveis' => $elegiveis,
            'perdidas' => $perdidas,
            'valor_perdido' => round((float) $valorPerdido, 2),
            'percentual_implantacao' => $elegiveis ? round($implantadas->count() / $elegiveis * 100, 2) : 0.0,
            'percentual_implantacao_valor' => $valorElegivel > 0 ? round($implantadas->sum('valor_calculado') / $valorElegivel * 100, 2) : 0.0,
            'percentual_estorno' => $total ? round($estornos->count() / $total * 100, 2) : 0.0,
            'percentual_declinio' => $total ? round($declinios->count() / $total * 100, 2) : 0.0,
            'percentual_perda' => $total ? round($perdidas / $total * 100, 2) : 0.0,
        ];
    }

    private function ranquear(Collection $vendedores, array $campos): array
    {
        return $vendedores->sort(function ($a, $b) use ($campos) {
            foreach ($campos as $campo) {
                $comparacao = $b[$campo] <=> $a[$campo];
                if ($comparacao !== 0) {
                    return $comparacao;
                }
            }

            return strcasecmp($a['vendedor'], $b['vendedor']);
        })->values()->map(function ($item, $indice) {
            $item['posicao'] = $indice + 1;

            return $item;
        })->all();
    }

    private function categoria(int $tabulacaoId): string
    {
        return match ($tabulacaoId) {
            Tabulations::IMPLANTADO => self::IMPLANTADA,
            Tabulations::ESTORNO => self::ESTORNO,
            Tabulations::DECLINIO => self::DECLINIO,
            default => self::EM_PROCESSO,
        };
    }

    private function aplicarCategoria(Builder $query, ?string $categoria): void
    {
        match ($categoria) {
            self::IMPLANTADA => $query->where('v.tabulacao_id', Tabulations::IMPLANTADO),
            self::ESTORNO => $query->where('v.tabulacao_id', Tabulations::ESTORNO),
            self::DECLINIO => $query->where('v.tabulacao_id', Tabulations::DECLINIO),
            self::EM_PROCESSO => $query->where(function (Builder $status) {
                $status->whereNull('v.tabulacao_id')
                    ->orWhereNotIn('v.tabulacao_id', [Tabulations::IMPLANTADO, Tabulations::ESTORNO, Tabulations::DECLINIO]);
            }),
            default => null,
        };
    }

    private function vendedoresDoAno(int $empresaId, int $ano): array
    {
        return DB::table('users as u')->join('vendas as v', 'v.user_id', '=', 'u.id')
            ->where('v.empresa_id', $empresaId)->whereYear('v.created_at', $ano)
            ->select('u.id', 'u.name')->distinct()->orderBy('u.name')->get()
            ->map(fn ($user) => ['id' => $user->id, 'nome' => $user->name])->all();
    }

    private function periodo(CarbonImmutable $inicio, CarbonImmutable $fim): array
    {
        return ['inicio' => $inicio->format('Y-m-d'), 'fim' => $fim->format('Y-m-d'), 'inicio_br' => $inicio->format('d/m/Y'), 'fim_br' => $fim->format('d/m/Y')];
    }

    private function valorSql(): string
    {
        return "COALESCE(v.valor_contrato, 0) + CASE WHEN v.angariacao_status = 'SIM' THEN COALESCE(v.angariacao_valor, 0) ELSE 0 END";
    }
}
