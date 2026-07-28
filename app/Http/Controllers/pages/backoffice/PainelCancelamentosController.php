<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\EtapaCancelamento;
use App\Enums\UserRole;
use App\Enums\ViaCancelamento;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\CancelamentoPosVendaRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Painel de Cancelamentos do pós-venda: pipeline do cancelamento do plano
 * anterior na operadora antiga. Entrada 100% manual, vinculada à venda.
 */
class PainelCancelamentosController extends Controller
{
    private const ALLOWED_ROLES = [
        UserRole::ADMINISTRATIVO,
        UserRole::BACKOFFICE,
        UserRole::DEVELOPER,
        UserRole::SUPERVISOR,
    ];

    public function __construct(private CancelamentoPosVendaRepositoryInterface $cancelamentos) {}

    public function index()
    {
        $this->checkAccess();

        return view('content.pages.backoffice.cancelamentos.index', [
            'colunas' => EtapaCancelamento::colunas(),
            'vias' => ViaCancelamento::labels(),
            'responsaveis' => $this->responsaveis(),
            'etapaDesistido' => [
                'id' => EtapaCancelamento::DESISTIDO->value,
                'label' => EtapaCancelamento::DESISTIDO->label(),
                'curto' => EtapaCancelamento::DESISTIDO->curto(),
                'gradiente' => EtapaCancelamento::DESISTIDO->gradiente(),
            ],
        ]);
    }

    public function dados(): JsonResponse
    {
        $this->checkAccess();

        return response()->json([
            'registros' => $this->cancelamentos->listar($this->empresaId()),
            'kpis' => $this->cancelamentos->kpis($this->empresaId()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'venda_id' => 'required|integer',
            'escopo' => 'required|in:APOLICE,BENEFICIARIO',
            'beneficiario_nome' => 'required_if:escopo,BENEFICIARIO|nullable|string|max:255',
            'via' => 'required|in:'.implode(',', ViaCancelamento::valores()),
            'operadora_anterior' => 'required|string|max:255',
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        $cancelamento = $this->cancelamentos->criar($this->empresaId(), $validated, Auth::id());

        if (! $cancelamento) {
            return response()->json([
                'success' => false,
                'message' => 'Contrato não encontrado.',
            ], 422);
        }

        return response()->json(['success' => true, 'id' => $cancelamento->id]);
    }

    public function show(int $id): JsonResponse
    {
        $this->checkAccess();

        $detalhe = $this->cancelamentos->detalhe($id, $this->empresaId());

        if (! $detalhe) {
            abort(404);
        }

        return response()->json($detalhe);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'protocolo' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string|max:2000',
        ]);

        if (! $this->cancelamentos->atualizar($id, $this->empresaId(), $validated, Auth::id())) {
            abort(404);
        }

        return response()->json(['success' => true]);
    }

    public function mover(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'etapa' => 'required|in:'.implode(',', EtapaCancelamento::valores()),
            'protocolo' => 'nullable|string|max:255',
        ]);

        $ok = $this->cancelamentos->moverEtapa(
            $id,
            $this->empresaId(),
            $validated['etapa'],
            Auth::id(),
            $validated['protocolo'] ?? null,
        );

        if (! $ok) {
            abort(404);
        }

        return response()->json(['success' => true]);
    }

    public function atribuir(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'responsavel_id' => 'nullable|integer|exists:users,id',
        ]);

        $ok = $this->cancelamentos->atribuirResponsavel(
            $id,
            $this->empresaId(),
            $validated['responsavel_id'] ?? null,
            Auth::id(),
        );

        if (! $ok) {
            abort(404);
        }

        return response()->json(['success' => true]);
    }

    public function desistir(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        if (! $this->cancelamentos->desistir($id, $this->empresaId(), Auth::id(), $validated['motivo'] ?? null)) {
            abort(404);
        }

        return response()->json(['success' => true]);
    }

    public function buscarContratos(Request $request): JsonResponse
    {
        $this->checkAccess();

        $termo = trim((string) $request->query('q', ''));

        return response()->json([
            'contratos' => $this->cancelamentos->buscarContratos($termo, $this->empresaId()),
        ]);
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
