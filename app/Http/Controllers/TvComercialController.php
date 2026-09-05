<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\MetaDiaria;
use App\Models\TvComercialAccessToken;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TvComercialController extends Controller
{
    /**
     * Exibe o painel da TV do comercial
     */
    public function painelTv(string $token): View
    {
        $this->resolvePublicAccess($token);

        return view('tv-comercial.painel', [
            'accessToken' => $token,
        ]);
    }

    /**
     * Retorna os dados das metas diárias para a TV (API pública)
     */
    public function getDadosTv(Request $request, string $token): JsonResponse
    {
        $empresaId = (int) $this->resolvePublicAccess($token)->empresa_id;

        return app(TenantContext::class)->run($empresaId, function () use ($request, $empresaId) {
            $configuracao = $this->configuracaoTv($empresaId);

            $data = $request->validate(['data' => ['nullable', 'date_format:Y-m-d']])['data']
                ?? Carbon::today()->format('Y-m-d');

            // Buscar apenas metas cadastradas para a data
            $metas = MetaDiaria::with([
                'user' => fn ($query) => $query->tenantMember($empresaId),
            ])
                ->where('empresa_id', $empresaId)
                ->where('data', $data)
                ->whereHas('user', function ($query) use ($empresaId) {
                    $query->where('empresa_id', $empresaId)
                        ->where('ativo', 'Y')
                        ->where('user_role_id', UserRole::VENDEDOR);
                })
                ->orderByDesc('cotacoes_realizadas')
                ->get()
                ->map(function ($meta) use ($configuracao) {
                    return [
                        'id' => $meta->id,
                        'user_id' => $meta->user_id,
                        'vendedor' => $meta->user->name,
                        'meta_cotacoes' => $meta->meta_cotacoes,
                        'cotacoes_realizadas' => $meta->cotacoes_realizadas,
                        'percentual_concluido' => $meta->percentual_concluido,
                        'status' => $meta->statusPara(
                            $configuracao['percentual_atencao'],
                            $configuracao['percentual_bom'],
                        ),
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
                'configuracao' => $configuracao,
                'ultima_atualizacao' => Carbon::now()->format('d/m/Y H:i:s'),
            ]);
        });
    }

    /**
     * Exibe a página de gerenciamento de metas diárias (admin)
     */
    public function gerenciar(): View
    {
        $empresaId = $this->activeEmpresaId();

        $vendedores = User::query()->tenantMember($empresaId)
            ->where('ativo', 'Y')
            ->where('user_role_id', UserRole::VENDEDOR)
            ->orderBy('name')
            ->get();

        $access = TvComercialAccessToken::query()
            ->where('empresa_id', $empresaId)
            ->where('active', true)
            ->first();

        return view('tv-comercial.gerenciar', [
            'vendedores' => $vendedores,
            'tvUrl' => $access ? route('tv-comercial.painel', ['token' => $access->token_encrypted]) : null,
            'configuracaoTv' => $this->configuracaoTv($empresaId),
        ]);
    }

    /**
     * Lista as metas para DataTables
     */
    public function listarMetas(Request $request): JsonResponse
    {
        $empresaId = $this->activeEmpresaId();
        $configuracao = $this->configuracaoTv($empresaId);
        $data = $request->validate([
            'data' => ['nullable', 'date_format:Y-m-d'],
        ])['data'] ?? Carbon::today()->format('Y-m-d');

        // Buscar apenas metas cadastradas para a data
        $metas = MetaDiaria::with([
            'user' => fn ($query) => $query->tenantMember((int) $empresaId),
        ])
            ->where('empresa_id', $empresaId)
            ->where('data', $data)
            ->whereHas('user', fn ($query) => $query->where('empresa_id', $empresaId))
            ->orderBy('cotacoes_realizadas', 'desc')
            ->get();

        $resultado = $metas->map(function ($meta) use ($configuracao) {
            return [
                'id' => $meta->id,
                'user_id' => $meta->user_id,
                'vendedor' => $meta->user->name,
                'meta_cotacoes' => $meta->meta_cotacoes,
                'cotacoes_realizadas' => $meta->cotacoes_realizadas,
                'percentual_concluido' => $meta->percentual_concluido,
                'status' => $meta->statusPara(
                    $configuracao['percentual_atencao'],
                    $configuracao['percentual_bom'],
                ),
            ];
        });

        return response()->json([
            'data' => $resultado,
            'configuracao' => $configuracao,
        ]);
    }

    /**
     * Salva/atualiza as metas diárias
     */
    public function salvarMetas(Request $request): JsonResponse
    {
        $empresaId = $this->activeEmpresaId();
        $validated = $request->validate([
            'data' => ['required', 'date_format:Y-m-d'],
            'metas' => ['required', 'array', 'min:1', 'max:500'],
            'metas.*.user_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('is_platform_admin', false)
                    ->where('ativo', 'Y')
                    ->where('user_role_id', UserRole::VENDEDOR)),
            ],
            'metas.*.meta_cotacoes' => ['required', 'integer', 'between:0,1000000'],
        ]);

        $data = $validated['data'];

        DB::transaction(function () use ($validated, $empresaId, $data): void {
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
        });

        return response()->json([
            'success' => true,
            'message' => 'Metas salvas com sucesso!',
        ]);
    }

    /**
     * Atualiza as cotações realizadas
     */
    public function atualizarCotacoes(Request $request): JsonResponse
    {
        $empresaId = $this->activeEmpresaId();
        $validated = $request->validate([
            'meta_id' => ['required', Rule::exists('metas_diarias', 'id')->where('empresa_id', $empresaId)],
            'cotacoes_realizadas' => ['required', 'integer', 'between:0,1000000'],
        ]);

        $meta = MetaDiaria::where('id', $validated['meta_id'])
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $meta->update([
            'cotacoes_realizadas' => $validated['cotacoes_realizadas'],
        ]);
        $configuracao = $this->configuracaoTv($empresaId);

        return response()->json([
            'success' => true,
            'message' => 'Cotações atualizadas com sucesso!',
            'percentual_concluido' => $meta->percentual_concluido,
            'status' => $meta->statusPara(
                $configuracao['percentual_atencao'],
                $configuracao['percentual_bom'],
            ),
        ]);
    }

    /**
     * Atualiza a meta de cotações
     */
    public function atualizarMeta(Request $request): JsonResponse
    {
        $empresaId = $this->activeEmpresaId();
        $validated = $request->validate([
            'meta_id' => ['required', Rule::exists('metas_diarias', 'id')->where('empresa_id', $empresaId)],
            'meta_cotacoes' => ['required', 'integer', 'between:0,1000000'],
        ]);

        $meta = MetaDiaria::where('id', $validated['meta_id'])
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $meta->update([
            'meta_cotacoes' => $validated['meta_cotacoes'],
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
        $empresaId = $this->activeEmpresaId();
        $validated = $request->validate([
            'meta_id' => ['required', Rule::exists('metas_diarias', 'id')->where('empresa_id', $empresaId)],
        ]);

        $meta = MetaDiaria::where('id', $validated['meta_id'])
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $meta->delete();

        return response()->json([
            'success' => true,
            'message' => 'Meta excluída com sucesso!',
        ]);
    }

    public function atualizarConfiguracao(Request $request): JsonResponse
    {
        $empresaId = $this->activeEmpresaId();
        $validated = $request->validate([
            'percentual_atencao' => ['required', 'integer', 'between:0,98'],
            'percentual_bom' => ['required', 'integer', 'between:1,99', 'gt:percentual_atencao'],
        ]);

        Empresa::query()->whereKey($empresaId)->update([
            'tv_percentual_atencao' => $validated['percentual_atencao'],
            'tv_percentual_bom' => $validated['percentual_bom'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Faixas de progresso atualizadas para esta empresa.',
            'configuracao' => [
                'percentual_atencao' => $validated['percentual_atencao'],
                'percentual_bom' => $validated['percentual_bom'],
            ],
        ]);
    }

    /**
     * Retorna o ranking semanal para a TV (API pública, sem auth)
     */
    public function getRankingTv(string $token): JsonResponse
    {
        $empresaId = (int) $this->resolvePublicAccess($token)->empresa_id;

        return app(TenantContext::class)->run($empresaId, function () use ($empresaId) {
            $dataInicio = Carbon::today()->startOfWeek();
            $dataFim = Carbon::today();

            $ranking = MetaDiaria::select(
                'user_id',
                DB::raw('SUM(cotacoes_realizadas) as total_realizadas'),
                DB::raw('SUM(meta_cotacoes) as total_meta')
            )
                ->where('empresa_id', $empresaId)
                ->whereBetween('data', [$dataInicio->format('Y-m-d'), $dataFim->format('Y-m-d')])
                ->whereHas('user', function ($query) use ($empresaId) {
                    $query->where('empresa_id', $empresaId)
                        ->where('ativo', 'Y')
                        ->where('user_role_id', UserRole::VENDEDOR);
                })
                ->groupBy('user_id')
                ->orderByDesc('total_realizadas')
                ->get();

            $userIds = $ranking->pluck('user_id');
            $users = User::query()->tenantMember($empresaId)->whereIn('id', $userIds)->pluck('name', 'id');

            $resultado = $ranking->map(function ($item) use ($users) {
                return [
                    'user_id' => $item->user_id,
                    'vendedor' => $users[$item->user_id] ?? 'Desconhecido',
                    'total_realizadas' => (int) $item->total_realizadas,
                    'total_meta' => (int) $item->total_meta,
                    'percentual' => $item->total_meta > 0
                        ? round(($item->total_realizadas / $item->total_meta) * 100, 1)
                        : 0,
                ];
            });

            return response()->json([
                'ranking' => $resultado,
                'total' => $resultado->sum('total_realizadas'),
                'periodo' => [
                    'data_inicio' => $dataInicio->format('d/m/Y'),
                    'data_fim' => $dataFim->format('d/m/Y'),
                ],
                'configuracao' => $this->configuracaoTv($empresaId),
                'ultima_atualizacao' => Carbon::now()->format('d/m/Y H:i:s'),
            ]);
        });
    }

    /**
     * Retorna o ranking de cotações por vendedor em um período
     * Dados vindos da tabela metas_diarias (cotacoes_realizadas)
     */
    public function rankingCotacoes(Request $request): JsonResponse
    {
        $empresaId = $this->activeEmpresaId();
        $validated = $request->validate([
            'periodo' => ['nullable', Rule::in(['hoje', 'semana', 'mes', 'personalizado'])],
            'data_inicio' => ['nullable', 'required_if:periodo,personalizado', 'date_format:Y-m-d'],
            'data_fim' => ['nullable', 'required_if:periodo,personalizado', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
        ]);
        $periodo = $validated['periodo'] ?? 'semana';

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
                $dataInicio = isset($validated['data_inicio'])
                    ? Carbon::parse($validated['data_inicio'])
                    : Carbon::today()->startOfWeek();
                $dataFim = isset($validated['data_fim'])
                    ? Carbon::parse($validated['data_fim'])
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
            ->whereHas('user', fn ($query) => $query->where('empresa_id', $empresaId))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->get();

        // Mapear com nomes dos vendedores
        $resultado = $ranking->map(function ($item) use ($empresaId) {
            $user = User::query()->tenantMember($empresaId)->find($item->user_id);

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
            ],
        ]);
    }

    public function regenerarAcesso(): JsonResponse
    {
        $empresaId = $this->activeEmpresaId();
        $token = Str::random(64);

        TvComercialAccessToken::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'created_by' => Auth::id(),
                'token_hash' => hash('sha256', $token),
                'token_encrypted' => $token,
                'active' => true,
                'last_used_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'url' => route('tv-comercial.painel', ['token' => $token]),
            'message' => 'Novo acesso gerado. A URL anterior foi revogada.',
        ]);
    }

    private function resolvePublicAccess(string $token): TvComercialAccessToken
    {
        abort_unless(strlen($token) === 64, 404);

        $access = TvComercialAccessToken::withoutGlobalScope('tenant')
            ->where('token_hash', hash('sha256', $token))
            ->where('active', true)
            ->firstOrFail();

        if (! $access->last_used_at || $access->last_used_at->lt(now()->subMinutes(5))) {
            app(TenantContext::class)->run(
                (int) $access->empresa_id,
                fn () => $access->forceFill(['last_used_at' => now()])->saveQuietly(),
            );
        }

        return $access;
    }

    private function activeEmpresaId(): int
    {
        return app(TenantContext::class)->id();
    }

    /** @return array{percentual_atencao: int, percentual_bom: int} */
    private function configuracaoTv(int $empresaId): array
    {
        $empresa = Empresa::query()->findOrFail($empresaId);

        return [
            'percentual_atencao' => (int) $empresa->tv_percentual_atencao,
            'percentual_bom' => (int) $empresa->tv_percentual_bom,
        ];
    }
}
