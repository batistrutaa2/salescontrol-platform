<?php

namespace App\Http\Controllers;

use App\Models\MetaDiaria;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TvComercialController extends Controller
{
    /**
     * Exibe o painel da TV do comercial
     */
    public function painelTv(Request $request): View
    {
        $empresaId = $request->query('empresa_id');

        return view('tv-comercial.painel', [
            'empresaId' => $empresaId
        ]);
    }

    /**
     * Retorna os dados das metas diárias para a TV (API pública)
     */
    public function getDadosTv(Request $request): JsonResponse
    {
        $empresaId = $request->query('empresa_id');

        if (!$empresaId) {
            return response()->json(['error' => 'empresa_id é obrigatório'], 400);
        }

        $data = $request->query('data', Carbon::today()->format('Y-m-d'));

        // Buscar apenas metas cadastradas para a data
        $metas = MetaDiaria::with('user')
            ->where('empresa_id', $empresaId)
            ->where('data', $data)
            ->whereHas('user', function($query) {
                $query->where('ativo', 'Y')
                      ->where('user_role_id', 1);
            })
            ->orderByDesc('cotacoes_realizadas')
            ->get()
            ->map(function ($meta) {
                return [
                    'id' => $meta->id,
                    'user_id' => $meta->user_id,
                    'vendedor' => $meta->user->name,
                    'meta_cotacoes' => $meta->meta_cotacoes,
                    'cotacoes_realizadas' => $meta->cotacoes_realizadas,
                    'percentual_concluido' => $meta->percentual_concluido,
                    'status' => $meta->status,
                ];
            });

        $totais = [
            'total_meta' => $metas->sum('meta_cotacoes'),
            'total_realizado' => $metas->sum('cotacoes_realizadas'),
            'percentual_geral' => $metas->sum('meta_cotacoes') > 0
                ? round(($metas->sum('cotacoes_realizadas') / $metas->sum('meta_cotacoes')) * 100, 2)
                : 0,
        ];

        return response()->json([
            'data' => $data,
            'data_formatada' => Carbon::parse($data)->format('d/m/Y'),
            'metas' => $metas,
            'totais' => $totais,
            'ultima_atualizacao' => Carbon::now()->format('d/m/Y H:i:s'),
        ]);
    }

    /**
     * Exibe a página de gerenciamento de metas diárias (admin)
     */
    public function gerenciar(): View
    {
        $empresaId = Auth::user()->empresa_id;

        $vendedores = User::where('empresa_id', $empresaId)
            ->where('ativo', 'Y')
            ->where('user_role_id', 1) // Apenas vendedores
            ->orderBy('name')
            ->get();

        return view('tv-comercial.gerenciar', [
            'vendedores' => $vendedores
        ]);
    }

    /**
     * Lista as metas para DataTables
     */
    public function listarMetas(Request $request): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;
        $data = $request->query('data', Carbon::today()->format('Y-m-d'));

        // Buscar apenas metas cadastradas para a data
        $metas = MetaDiaria::with('user')
            ->where('empresa_id', $empresaId)
            ->where('data', $data)
            ->orderBy('cotacoes_realizadas', 'desc')
            ->get();

        $resultado = $metas->map(function ($meta) {
            return [
                'id' => $meta->id,
                'user_id' => $meta->user_id,
                'vendedor' => $meta->user->name,
                'meta_cotacoes' => $meta->meta_cotacoes,
                'cotacoes_realizadas' => $meta->cotacoes_realizadas,
                'percentual_concluido' => $meta->percentual_concluido,
                'status' => $meta->status,
            ];
        });

        return response()->json(['data' => $resultado]);
    }

    /**
     * Salva/atualiza as metas diárias
     */
    public function salvarMetas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data' => 'required|date',
            'metas' => 'required|array',
            'metas.*.user_id' => 'required|exists:users,id',
            'metas.*.meta_cotacoes' => 'required|integer|min:0',
        ]);

        $empresaId = Auth::user()->empresa_id;
        $data = $validated['data'];

        foreach ($validated['metas'] as $metaData) {
            MetaDiaria::updateOrCreate(
                [
                    'empresa_id' => $empresaId,
                    'user_id' => $metaData['user_id'],
                    'data' => $data,
                ],
                [
                    'meta_cotacoes' => $metaData['meta_cotacoes'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Metas salvas com sucesso!'
        ]);
    }

    /**
     * Atualiza as cotações realizadas
     */
    public function atualizarCotacoes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meta_id' => 'required|exists:metas_diarias,id',
            'cotacoes_realizadas' => 'required|integer|min:0',
        ]);

        $meta = MetaDiaria::where('id', $validated['meta_id'])
            ->where('empresa_id', Auth::user()->empresa_id)
            ->firstOrFail();

        $meta->update([
            'cotacoes_realizadas' => $validated['cotacoes_realizadas']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cotações atualizadas com sucesso!',
            'percentual_concluido' => $meta->percentual_concluido,
            'status' => $meta->status,
        ]);
    }

    /**
     * Atualiza a meta de cotações
     */
    public function atualizarMeta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meta_id' => 'required|exists:metas_diarias,id',
            'meta_cotacoes' => 'required|integer|min:0',
        ]);

        $meta = MetaDiaria::where('id', $validated['meta_id'])
            ->where('empresa_id', Auth::user()->empresa_id)
            ->firstOrFail();

        $meta->update([
            'meta_cotacoes' => $validated['meta_cotacoes']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meta atualizada com sucesso!',
        ]);
    }

    /**
     * Deleta uma meta
     */
    public function deletarMeta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meta_id' => 'required|exists:metas_diarias,id',
        ]);

        $meta = MetaDiaria::where('id', $validated['meta_id'])
            ->where('empresa_id', Auth::user()->empresa_id)
            ->firstOrFail();

        $meta->delete();

        return response()->json([
            'success' => true,
            'message' => 'Meta excluída com sucesso!',
        ]);
    }

    /**
     * Retorna o ranking de cotações por vendedor em um período
     * Dados vindos da tabela metas_diarias (cotacoes_realizadas)
     */
    public function rankingCotacoes(Request $request): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;
        $periodo = $request->query('periodo', 'semana');

        // Determinar datas baseado no período
        $dataInicio = null;
        $dataFim = Carbon::today();

        switch ($periodo) {
            case 'hoje':
                $dataInicio = Carbon::today();
                $dataFim = Carbon::today();
                break;
            case 'semana':
                $dataInicio = Carbon::today()->startOfWeek();
                $dataFim = Carbon::today()->endOfWeek();
                break;
            case 'mes':
                $dataInicio = Carbon::today()->startOfMonth();
                $dataFim = Carbon::today()->endOfMonth();
                break;
            case 'personalizado':
                $dataInicio = $request->query('data_inicio')
                    ? Carbon::parse($request->query('data_inicio'))
                    : Carbon::today()->startOfWeek();
                $dataFim = $request->query('data_fim')
                    ? Carbon::parse($request->query('data_fim'))
                    : Carbon::today();
                break;
            default:
                $dataInicio = Carbon::today()->startOfWeek();
                $dataFim = Carbon::today()->endOfWeek();
        }

        // Buscar cotações realizadas agrupadas por vendedor no período
        $ranking = MetaDiaria::select('user_id', DB::raw('SUM(cotacoes_realizadas) as total'))
            ->where('empresa_id', $empresaId)
            ->whereBetween('data', [$dataInicio->format('Y-m-d'), $dataFim->format('Y-m-d')])
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->get();

        // Mapear com nomes dos vendedores
        $resultado = $ranking->map(function ($item) {
            $user = User::find($item->user_id);
            return [
                'user_id' => $item->user_id,
                'vendedor' => $user ? $user->name : 'Desconhecido',
                'total' => (int) $item->total,
            ];
        });

        $total = $resultado->sum('total');

        return response()->json([
            'success' => true,
            'data' => $resultado,
            'total' => $total,
            'periodo' => [
                'tipo' => $periodo,
                'data_inicio' => $dataInicio->format('d/m/Y'),
                'data_fim' => $dataFim->format('d/m/Y'),
            ]
        ]);
    }
}
