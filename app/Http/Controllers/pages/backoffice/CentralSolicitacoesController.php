<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\NaturezaEtapaSolicitacao;
use App\Enums\TipoSolicitacaoPosVenda;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PosVendaFluxoEtapa;
use App\Models\PosVendaSolicitacao;
use App\Models\User;
use App\Notifications\DemandaVendedorConcluida;
use App\Repositories\Contracts\PosVendaSolicitacaoRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Central de Solicitações do Pós-Venda: todas as tarefas rotineiras do setor
 * (cancelamento, portabilidade, boleto, reembolso...) mapeadas com prazo
 * manual e fluxo de etapas configurável por tipo.
 */
class CentralSolicitacoesController extends Controller
{
    private const ALLOWED_ROLES = [
        UserRole::ADMINISTRATIVO,
        UserRole::BACKOFFICE,
        UserRole::DEVELOPER,
        UserRole::SUPERVISOR,
    ];

    public function __construct(private PosVendaSolicitacaoRepositoryInterface $solicitacoes) {}

    public function index()
    {
        $this->checkAccess();

        PosVendaFluxoEtapa::seedDefaults($this->empresaId());

        return view('content.pages.backoffice.solicitacoes.index', [
            'tipos' => TipoSolicitacaoPosVenda::labels(),
            'naturezas' => NaturezaEtapaSolicitacao::labels(),
            'etapasPorTipo' => $this->solicitacoes->etapas($this->empresaId()),
            'responsaveis' => $this->responsaveis(),
        ]);
    }

    public function dados(Request $request): JsonResponse
    {
        $this->checkAccess();

        $filtros = $request->only(['tipo', 'responsavel_id', 'etapa_id', 'status', 'busca']);

        return response()->json([
            'registros' => $this->solicitacoes->listar($this->empresaId(), $filtros),
            'kpis' => $this->solicitacoes->kpis($this->empresaId()),
            'etapas' => $this->solicitacoes->etapas($this->empresaId()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'venda_id' => 'required|integer',
            'tipo' => 'required|in:'.implode(',', array_keys(TipoSolicitacaoPosVenda::labels())),
            'titulo' => 'nullable|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'data_limite' => 'nullable|date_format:Y-m-d',
            'responsavel_id' => 'nullable|integer|exists:users,id',
        ]);

        $solicitacao = $this->solicitacoes->criar($this->empresaId(), $validated, Auth::id());

        if (! $solicitacao) {
            return response()->json([
                'success' => false,
                'message' => 'Contrato não encontrado.',
            ], 422);
        }

        return response()->json(['success' => true, 'id' => $solicitacao->id]);
    }

    public function show(int $id): JsonResponse
    {
        $this->checkAccess();

        $detalhe = $this->solicitacoes->detalhe($id, $this->empresaId());

        if (! $detalhe) {
            abort(404);
        }

        return response()->json($detalhe);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'titulo' => 'sometimes|nullable|string|max:255',
            'descricao' => 'sometimes|nullable|string|max:2000',
            'data_limite' => 'sometimes|nullable|date_format:Y-m-d',
            'responsavel_id' => 'sometimes|nullable|integer|exists:users,id',
        ]);

        if (! $this->solicitacoes->atualizar($id, $this->empresaId(), $validated, Auth::id())) {
            abort(404);
        }

        return response()->json(['success' => true]);
    }

    public function mover(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'etapa_id' => 'required|integer',
            'observacao' => 'nullable|string|max:500',
        ]);

        $statusAnterior = PosVendaSolicitacao::where('empresa_id', $this->empresaId())
            ->where('id', $id)
            ->value('status');

        $erro = $this->solicitacoes->moverEtapa(
            $id,
            $this->empresaId(),
            (int) $validated['etapa_id'],
            Auth::id(),
            $validated['observacao'] ?? null,
        );

        if ($erro === 'NAO_ENCONTRADA') {
            abort(404);
        }
        if ($erro === 'ETAPA_INVALIDA') {
            return response()->json([
                'success' => false,
                'message' => 'A etapa escolhida não pertence ao fluxo deste tipo de solicitação.',
            ], 422);
        }

        $this->notificarVendedorSeConcluida($id, $statusAnterior);

        return response()->json(['success' => true]);
    }

    /**
     * Solicitação aberta pelo vendedor recém-concluída -> avisa o criador pelo mascote.
     */
    private function notificarVendedorSeConcluida(int $id, ?string $statusAnterior): void
    {
        if ($statusAnterior === PosVendaSolicitacao::STATUS_CONCLUIDA) {
            return;
        }

        $solicitacao = PosVendaSolicitacao::with('venda:id,nome_contrato', 'criador:id')
            ->where('empresa_id', $this->empresaId())
            ->find($id);

        if (! $solicitacao
            || $solicitacao->status !== PosVendaSolicitacao::STATUS_CONCLUIDA
            || $solicitacao->origem !== PosVendaSolicitacao::ORIGEM_VENDEDOR
            || ! $solicitacao->criador) {
            return;
        }

        $solicitacao->criador->notify(new DemandaVendedorConcluida(
            demandaId: $solicitacao->id,
            cliente: $solicitacao->venda?->nome_contrato ?? 'cliente',
            tipoLabel: TipoSolicitacaoPosVenda::tryFrom($solicitacao->tipo)?->label() ?? ($solicitacao->titulo ?? 'Solicitação'),
            url: route('comercial.demandasVendedor'),
            concluidaPorNome: Auth::user()->name ?? null,
        ));
    }

    public function buscarContratos(Request $request): JsonResponse
    {
        $this->checkAccess();

        $termo = trim((string) $request->query('q', ''));

        return response()->json([
            'contratos' => $this->solicitacoes->buscarContratos($termo, $this->empresaId()),
        ]);
    }

    // ---------------------------------------------------------------
    // Etapas do fluxo (configuráveis pela pós-venda)
    // ---------------------------------------------------------------

    public function storeEtapa(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'tipo' => 'required|in:'.implode(',', array_keys(TipoSolicitacaoPosVenda::labels())),
            'nome' => 'required|string|max:60',
            'cor' => 'nullable|string|max:20',
            'natureza' => 'nullable|in:'.implode(',', array_keys(NaturezaEtapaSolicitacao::labels())),
        ]);

        $etapa = $this->solicitacoes->criarEtapa($this->empresaId(), $validated);

        return response()->json(['success' => true, 'id' => $etapa->id]);
    }

    public function updateEtapa(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'nome' => 'sometimes|required|string|max:60',
            'cor' => 'sometimes|nullable|string|max:20',
            'natureza' => 'sometimes|required|in:'.implode(',', array_keys(NaturezaEtapaSolicitacao::labels())),
        ]);

        if (! $this->solicitacoes->atualizarEtapa($id, $this->empresaId(), $validated)) {
            abort(404);
        }

        return response()->json(['success' => true]);
    }

    public function destroyEtapa(int $id): JsonResponse
    {
        $this->checkAccess();

        $erro = $this->solicitacoes->excluirEtapa($id, $this->empresaId());

        if ($erro === 'NAO_ENCONTRADA') {
            abort(404);
        }
        if ($erro === 'COM_SOLICITACOES') {
            return response()->json([
                'success' => false,
                'message' => 'Há solicitações nesta etapa. Mova os cards antes de excluí-la.',
            ], 422);
        }
        if ($erro === 'ULTIMA_CONCLUIDA') {
            return response()->json([
                'success' => false,
                'message' => 'O fluxo precisa manter ao menos uma etapa de conclusão.',
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function reordenarEtapas(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'tipo' => 'required|in:'.implode(',', array_keys(TipoSolicitacaoPosVenda::labels())),
            'ordem' => 'required|array|min:1',
            'ordem.*' => 'integer',
        ]);

        if (! $this->solicitacoes->reordenarEtapas($this->empresaId(), $validated['tipo'], $validated['ordem'])) {
            return response()->json([
                'success' => false,
                'message' => 'A ordem enviada não corresponde às etapas deste fluxo.',
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    // ---------------------------------------------------------------

    private function checkAccess(): void
    {
        if (! in_array(Auth::user()->user_role_id, self::ALLOWED_ROLES)) {
            abort(403, 'Acesso não autorizado.');
        }
    }

    private function empresaId(): int
    {
        return Auth::user()->empresa_id;
    }

    private function responsaveis()
    {
        return User::where('empresa_id', $this->empresaId())
            ->whereIn('user_role_id', self::ALLOWED_ROLES)
            ->where('ativo', 'Y')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
