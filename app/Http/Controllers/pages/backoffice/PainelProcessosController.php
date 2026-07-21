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

        return view('content.pages.backoffice.painel-processos', [
            'responsaveis' => $this->responsaveis(),
            'tipos' => TipoDemandaContrato::labels(),
        ]);
    }

    private const POR_PAGINA = 20;

    public function data(Request $request)
    {
        abort_unless($this->podeGerir(), 403);
        $empresaId = Auth::user()->empresa_id;

        $filtros = $request->only(['grupo', 'tipo', 'responsavel_id', 'busca']);
        $filtros['so_atrasados'] = $request->boolean('so_atrasados');

        $completa = $this->processos->filaOperacional($empresaId, []);
        $filtrada = collect($this->processos->filaOperacional($empresaId, array_filter($filtros, fn ($v) => $v !== null && $v !== '')));

        $total = $filtrada->count();
        $totalPaginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min(max(1, (int) $request->input('pagina', 1)), $totalPaginas);
        $fila = $filtrada->slice(($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA)->values();

        return response()->json([
            'success' => true,
            'kpis' => [
                'cancelamentos_abertos' => collect($completa)->where('grupo', 'cancelamentos')->count(),
                'portabilidades_abertas' => collect($completa)->where('tipo', 'PORTABILIDADE')->count(),
                'atrasados' => collect($completa)->where('atrasado', true)->count(),
                'total_abertos' => count($completa),
                'concluidos_mes' => $this->processos->concluidosNoMes($empresaId),
            ],
            'fila' => $fila,
            'paginacao' => [
                'pagina' => $pagina,
                'por_pagina' => self::POR_PAGINA,
                'total' => $total,
                'total_paginas' => $totalPaginas,
                'de' => $total ? (($pagina - 1) * self::POR_PAGINA) + 1 : 0,
                'ate' => min($pagina * self::POR_PAGINA, $total),
            ],
            'fases_portabilidade' => \App\Enums\FasePortabilidade::fluxo(),
        ]);
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
