<?php

namespace App\Modules\LkBeneficios\Services;

use App\Modules\LkBeneficios\Enums\StatusContrato;
use App\Modules\LkBeneficios\Enums\TipoBeneficio;
use App\Modules\LkBeneficios\Models\Contrato;
use App\Modules\LkBeneficios\Repositories\Contracts\ContratoRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MetricasService
{
    public function __construct(private ContratoRepositoryInterface $contratos)
    {
    }

    public function kpis(int $empresaId): array
    {
        $porStatus = $this->contratos->countByStatus($empresaId);
        $ativos = $porStatus[StatusContrato::ATIVO] ?? 0;
        $mrr = $this->contratos->somaMrr($empresaId);
        $totalContratos = array_sum($porStatus);

        $vidas = (int) Contrato::where('empresa_id', $empresaId)
            ->where('status', StatusContrato::ATIVO)
            ->sum('vidas_total');

        $ticketMedio = $ativos > 0 ? $mrr / $ativos : 0;

        return [
            'contratos_ativos' => $ativos,
            'contratos_total' => $totalContratos,
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'vidas_total' => $vidas,
            'ticket_medio' => round($ticketMedio, 2),
            'por_status' => $porStatus,
        ];
    }

    public function distribuicaoPorTipo(int $empresaId): array
    {
        $base = array_fill_keys(TipoBeneficio::all(), 0);

        $rows = Contrato::query()
            ->where('lk_beneficios_contratos.empresa_id', $empresaId)
            ->join('lk_beneficios_produtos', 'lk_beneficios_produtos.id', '=', 'lk_beneficios_contratos.produto_id')
            ->select('lk_beneficios_produtos.tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('lk_beneficios_produtos.tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        return array_merge($base, $rows);
    }

    public function reajustesProximos(int $empresaId, int $dias = 30)
    {
        return $this->contratos->reajustesProximos($empresaId, $dias);
    }
}
