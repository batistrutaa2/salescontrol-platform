<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\ProcessoVendaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Painel operacional de processos (visão do gestor): quantos cancelamentos e
 * portabilidades estão em aberto, quais estão atrasados (SLA por tipo), e com
 * qual responsável — para não perder o controle da operação (nem quando alguém sai).
 */
class PainelProcessosController extends Controller
{
    private const GESTORES = [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::SUPERVISOR];

    public function __construct(
        private ProcessoVendaRepositoryInterface $processos,
    ) {}

    public function index()
    {
        abort_unless($this->podeGerir(), 403);

        // Só os tipos que o painel exibe (cancelamentos e portabilidade).
        $grupos = TipoDemandaContrato::grupos();
        $gruposPainel = config('processos.grupos_painel', ['cancelamentos', 'portabilidade']);
        $tipos = collect(TipoDemandaContrato::labels())
            ->filter(fn ($label, $tipo) => in_array($grupos[$tipo] ?? '', $gruposPainel, true))
            ->all();

        return view('content.pages.backoffice.painel-processos', [
            'responsaveis' => $this->responsaveis(),
            'tipos' => $tipos,
        ]);
    }

    public function data(Request $request)
    {
        abort_unless($this->podeGerir(), 403);
        $empresaId = Auth::user()->empresa_id;

        $filtros = $request->only(['grupo', 'tipo', 'responsavel_id', 'busca']);

        // Esteira kanban: sem paginação (volume limitado pelo corte de abertura).
        // Os KPIs vêm da fila completa; a fila filtrada abastece os boards.
        $completa = collect($this->processos->filaOperacional($empresaId, []));
        $fila = collect($this->processos->filaOperacional($empresaId, array_filter($filtros, fn ($v) => $v !== null && $v !== '')));

        return response()->json([
            'success' => true,
            'kpis' => [
                'atrasados' => $completa->where('urgencia', 'atrasado')->count(),
                'vencendo' => $completa->where('urgencia', 'vencendo')->count(),
                'aguardando_implantacao' => $completa->where('bloqueado', true)->count(),
                'concluidos_mes' => $this->processos->concluidosNoMes($empresaId),
                'cancelamentos_abertos' => $completa->where('grupo', 'cancelamentos')->count(),
                'portabilidades_abertas' => $completa->where('grupo', 'portabilidade')->count(),
            ],
            'fila' => $fila->values(),
            'fases_cancelamento' => \App\Enums\FaseCancelamento::fluxo(),
            'fases_portabilidade' => \App\Enums\FasePortabilidade::fluxo(),
        ]);
    }

    /** Avança a fase de um cancelamento (demanda); a fase final conclui o processo. */
    public function faseCancelamento(Request $request)
    {
        abort_unless($this->podeGerir(), 403);

        $dados = $request->validate([
            'id' => 'required|integer',
            'fase' => ['required', 'string', 'in:'.implode(',', array_column(\App\Enums\FaseCancelamento::fluxo(), 'value'))],
        ]);

        $demanda = $this->processos->atualizarCancelamento(
            (int) $dados['id'], Auth::user()->empresa_id, ['fase' => $dados['fase']], Auth::id(),
        );

        return response()->json([
            'success' => (bool) $demanda,
            'message' => $demanda ? 'Fase do cancelamento atualizada.' : 'Processo não encontrado.',
        ], $demanda ? 200 : 404);
    }

    /** Avança a fase de uma portabilidade (fase final fecha o processo). */
    public function fasePortabilidade(Request $request)
    {
        abort_unless($this->podeGerir(), 403);

        $dados = $request->validate([
            'id' => 'required|integer',
            'fase' => ['required', 'string', 'in:'.implode(',', array_column(\App\Enums\FasePortabilidade::fluxo(), 'value'))],
        ]);

        $ok = $this->processos->atualizarFasePortabilidade((int) $dados['id'], Auth::user()->empresa_id, $dados['fase'], Auth::id());

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Fase da portabilidade atualizada.' : 'Portabilidade não encontrada.',
        ], $ok ? 200 : 404);
    }

    public function atribuir(Request $request)
    {
        abort_unless($this->podeGerir(), 403);

        $dados = $request->validate([
            'fonte' => 'required|in:demanda,portabilidade',
            'id' => 'required|integer',
            'responsavel_id' => 'nullable|integer|exists:users,id',
        ]);

        $ok = $this->processos->atribuirResponsavel(
            $dados['fonte'], (int) $dados['id'], Auth::user()->empresa_id, $dados['responsavel_id'] ?? null,
        );

        return response()->json(['success' => $ok, 'message' => $ok ? 'Responsável atualizado.' : 'Processo não encontrado.'], $ok ? 200 : 404);
    }

    public function concluir(Request $request)
    {
        abort_unless($this->podeGerir(), 403);

        $dados = $request->validate([
            'fonte' => 'required|in:demanda,portabilidade',
            'id' => 'required|integer',
        ]);

        $ok = $this->processos->concluirProcesso($dados['fonte'], (int) $dados['id'], Auth::user()->empresa_id, Auth::id());

        return response()->json(['success' => $ok, 'message' => $ok ? 'Processo concluído.' : 'Processo não encontrado.'], $ok ? 200 : 404);
    }

    private function podeGerir(): bool
    {
        return in_array(Auth::user()->user_role_id, self::GESTORES, true);
    }

    private function responsaveis()
    {
        return User::select('id', 'name')
            ->where('empresa_id', Auth::user()->empresa_id)
            ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::BACKOFFICE, UserRole::SUPERVISOR])
            ->where('ativo', 'Y')
            ->orderBy('name')
            ->get();
    }
}
