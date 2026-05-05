<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Models\Produto;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use App\Modules\LkBeneficios\Services\AquisicaoLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function __construct(
        private LeadRepositoryInterface $leads,
        private AquisicaoLeadService $aquisicao,
    ) {
    }

    public function novo()
    {
        $empresaId = Auth::user()->empresa_id;
        $produtos = Produto::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('content.pages.lk-beneficios.leads.novo', compact('produtos'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'produto_interesse_id' => 'required|integer|exists:lk_beneficios_produtos,id',
            'cliente_tipo' => 'required|in:PF,PJ',
            'cpf_cnpj' => 'required|string|max:20',
            'nome' => 'required|string|max:150',
            'email' => 'nullable|email|max:120',
            'telefone' => 'nullable|string|max:30',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        try {
            $lead = $this->aquisicao->criarManual(
                $data,
                Auth::user()->empresa_id,
                Auth::id(),
            );
            return response()->json(['message' => 'Lead criado.', 'lead_id' => $lead->id], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show(int $id)
    {
        $empresaId = Auth::user()->empresa_id;
        $lead = $this->leads->findByEmpresa($id, $empresaId);
        abort_unless($lead, 404);

        return view('content.pages.lk-beneficios.leads.show', compact('lead'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->leads->soft($id, Auth::user()->empresa_id);
        return response()->json(['message' => 'Lead descartado.']);
    }

    public function storeComentario(Request $request, int $leadId): JsonResponse
    {
        $data = $request->validate([
            'anotacao' => 'required|string|max:5000',
        ]);

        $comentario = $this->leads->adicionarComentario(
            $leadId,
            Auth::user()->empresa_id,
            Auth::id(),
            $data['anotacao']
        );

        return response()->json([
            'message' => 'Comentário adicionado.',
            'comentario' => [
                'id' => $comentario->id,
                'anotacao' => $comentario->anotacao,
                'created_at' => $comentario->created_at->toIso8601String(),
                'user' => [
                    'id' => $comentario->user?->id,
                    'name' => $comentario->user?->name,
                ],
            ],
        ], 201);
    }

    public function destroyComentario(int $leadId, int $comentarioId): JsonResponse
    {
        $this->leads->removerComentario($comentarioId, $leadId, Auth::user()->empresa_id);

        return response()->json(['message' => 'Comentário removido.']);
    }

    public function updateInformacaoFixada(Request $request, int $leadId): JsonResponse
    {
        $data = $request->validate([
            'informacao_fixada' => 'nullable|string|max:1000',
        ]);

        $lead = $this->leads->atualizarInformacaoFixada(
            $leadId,
            Auth::user()->empresa_id,
            $data['informacao_fixada'] ?? null
        );

        return response()->json([
            'message' => $lead->informacao_fixada ? 'Informação fixada.' : 'Informação removida.',
            'informacao_fixada' => $lead->informacao_fixada,
        ]);
    }
}
