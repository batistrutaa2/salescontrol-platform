<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Enums\StatusLead;
use App\Modules\LkBeneficios\Models\Produto;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KanbanController extends Controller
{
    public function __construct(private LeadRepositoryInterface $leads)
    {
    }

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;

        $colunas = [];
        $statusCores = [];
        foreach (StatusLead::all() as $status) {
            $cores = StatusLead::corGradiente($status);
            $colunas[] = [
                'id' => $status,
                'label' => StatusLead::label($status),
                'cor_start' => $cores[0],
                'cor_end' => $cores[1],
            ];
            $statusCores[$status] = ['start' => $cores[0], 'end' => $cores[1]];
        }

        $produtos = Produto::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'tipo']);

        return view('content.pages.lk-beneficios.leads.kanban', compact('colunas', 'produtos', 'statusCores'));
    }

    public function dados(Request $request): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;

        $filtros = $request->only(['user_id', 'produto_id', 'busca']);
        $agrupado = $this->leads->queryKanban($empresaId, $filtros);

        $resultado = [];
        foreach ($agrupado as $status => $leads) {
            $resultado[$status] = array_map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'nome' => $lead->nome,
                    'cpf_cnpj' => $lead->cpf_cnpj,
                    'produto_nome' => $lead->produtoInteresse?->nome,
                    'produto_tipo' => $lead->produtoInteresse?->tipo,
                    'corretor' => $lead->user?->name,
                    'origem' => $lead->origem,
                    'dias_no_status' => (int) now()->diffInDays($lead->data_ultima_movimentacao),
                    'updated_at' => $lead->updated_at->toIso8601String(),
                ];
            }, $leads);
        }

        return response()->json(['colunas' => $resultado]);
    }

    public function mover(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => 'required|integer',
            'novo_status' => 'required|in:' . implode(',', StatusLead::all()),
            'ordem' => 'nullable|array',
            'ordem.*' => 'integer',
            'observacao' => 'nullable|string|max:500',
        ]);

        $empresaId = Auth::user()->empresa_id;

        try {
            $lead = $this->leads->moverStatus(
                (int) $data['lead_id'],
                $empresaId,
                $data['novo_status'],
                Auth::id(),
                $data['observacao'] ?? null,
            );

            if (! empty($data['ordem'])) {
                $this->leads->reordenar($empresaId, $data['novo_status'], $data['ordem']);
            }

            return response()->json(['message' => 'Lead movido.', 'lead_id' => $lead->id]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
