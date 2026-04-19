<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Enums\StatusContrato;
use App\Modules\LkBeneficios\Enums\TipoBeneficio;
use App\Modules\LkBeneficios\Services\MetricasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private MetricasService $metricas)
    {
    }

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;

        $kpis = $this->metricas->kpis($empresaId);
        $porTipo = $this->metricas->distribuicaoPorTipo($empresaId);
        $reajustes = $this->metricas->reajustesProximos($empresaId, 60);

        return view('content.pages.lk-beneficios.dashboard', compact('kpis', 'porTipo', 'reajustes'));
    }

    public function metricasJson(): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;

        return response()->json([
            'kpis' => $this->metricas->kpis($empresaId),
            'por_tipo' => $this->metricas->distribuicaoPorTipo($empresaId),
            'labels' => [
                'status' => collect(StatusContrato::all())->mapWithKeys(fn ($s) => [$s => StatusContrato::label($s)]),
                'tipos' => collect(TipoBeneficio::all())->mapWithKeys(fn ($t) => [$t => TipoBeneficio::label($t)]),
            ],
        ]);
    }
}
