<?php

namespace App\Http\Controllers\pages\relatorios;

use App\Http\Controllers\Controller;
use App\Services\RelatorioQualidadeVendasService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelatorioQualidadeVendasController extends Controller
{
    public function __construct(private readonly RelatorioQualidadeVendasService $service) {}

    public function index(): View
    {
        return view('content.pages.relatorios.qualidade-vendas', [
            'anoAtual' => now('America/Sao_Paulo')->year,
            'hoje' => now('America/Sao_Paulo')->format('Y-m-d'),
        ]);
    }

    public function dados(Request $request): JsonResponse
    {
        [$inicio, $fim, $vendedorId] = $this->filtros($request);

        return response()->json([
            'success' => true,
            'dados' => $this->service->resumo(auth()->user()->empresa_id, $inicio, $fim, $vendedorId),
        ]);
    }

    public function propostas(Request $request): JsonResponse
    {
        [$inicio, $fim, $vendedorId] = $this->filtros($request);
        $request->validate([
            'categoria' => 'nullable|in:implantada,em_processo,estorno,declinio',
            'por_pagina' => 'nullable|integer|min:10|max:100',
        ]);

        return response()->json($this->service->detalhes(
            auth()->user()->empresa_id,
            $inicio,
            $fim,
            $vendedorId,
            $request->input('categoria'),
            (int) $request->input('por_pagina', 20),
        ));
    }

    private function filtros(Request $request): array
    {
        $ano = now('America/Sao_Paulo')->year;
        $request->validate([
            'data_inicio' => "required|date_format:Y-m-d|after_or_equal:{$ano}-01-01|before_or_equal:{$ano}-12-31",
            'data_fim' => "required|date_format:Y-m-d|after_or_equal:data_inicio|before_or_equal:{$ano}-12-31",
            'vendedor_id' => 'nullable|integer',
        ]);

        return [
            CarbonImmutable::parse($request->input('data_inicio'), 'America/Sao_Paulo'),
            CarbonImmutable::parse($request->input('data_fim'), 'America/Sao_Paulo'),
            $request->filled('vendedor_id') ? (int) $request->input('vendedor_id') : null,
        ];
    }
}
