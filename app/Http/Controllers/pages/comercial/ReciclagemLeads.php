<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Models\PreditivaConfiguracao;
use App\Repositories\Eloquent\ContatosRepository;
use App\Services\ReciclagemLeadsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReciclagemLeads extends Controller
{
    public function __construct(
        private ContatosRepository $contatosRepository,
        private ReciclagemLeadsService $reciclagemService
    ) {}

    public function index()
    {
        $empresaId = $this->tenantId();
        $config = PreditivaConfiguracao::getOrDefault($empresaId);
        $resumo = $this->contatosRepository->getResumoReciclagem(
            $empresaId,
            (int) $config->dias_sem_contato_reenvio,
            (int) $config->indicadores_janela_dias,
        );

        return view('content.pages.comercial.reciclagem-leads', compact('resumo', 'config'));
    }

    /** Server-side DataTable dos leads frios elegiveis. */
    public function getElegiveis(Request $request)
    {
        $empresaId = $this->tenantId();
        $dias = (int) PreditivaConfiguracao::getOrDefault($empresaId)->dias_sem_contato_reenvio;
        $this->validarDataTable($request);

        return response()->json($this->contatosRepository->getLeadsFriosElegiveis($empresaId, $dias, $request));
    }

    /** Recalcula os KPIs dos baldes (chamado apos envios / mudanca de config). */
    public function resumo()
    {
        $empresaId = $this->tenantId();
        $config = PreditivaConfiguracao::getOrDefault($empresaId);

        return response()->json([
            'success' => true,
            'resumo' => $this->contatosRepository->getResumoReciclagem(
                $empresaId,
                (int) $config->dias_sem_contato_reenvio,
                (int) $config->indicadores_janela_dias,
            ),
        ]);
    }

    /** Envio manual: ids selecionados, ou todos os elegiveis (todos=true). */
    public function enviar(Request $request)
    {
        $empresaId = $this->tenantId();
        $data = $request->validate([
            'todos' => ['sometimes', 'boolean'],
            'ids' => ['nullable', 'array', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);
        $todos = $request->boolean('todos');
        $ids = $data['ids'] ?? [];
        if (! $todos && $ids === []) {
            throw ValidationException::withMessages([
                'ids' => 'Selecione ao menos um lead ou marque a opção de enviar todos os elegíveis.',
            ]);
        }

        $resultado = $this->reciclagemService->enviarElegiveisEmLote(
            $empresaId,
            $todos ? null : $ids,
            'MANUAL',
            Auth::id(),
            null
        );

        return response()->json([
            'success' => true,
            'message' => "{$resultado['enviados']} lead(s) enviados para a preditiva.",
            'resultado' => $resultado,
        ]);
    }

    public function getConfig()
    {
        $empresaId = $this->tenantId();
        $config = PreditivaConfiguracao::getOrDefault($empresaId);

        return response()->json([
            'success' => true,
            'config' => [
                'dias_sem_contato_reenvio' => (int) $config->dias_sem_contato_reenvio,
                'envio_automatico_ativo' => (bool) $config->envio_automatico_ativo,
                'limite_envio_diario' => (int) $config->limite_envio_diario,
                'mascote_dias_sem_atividade' => (int) $config->mascote_dias_sem_atividade,
                'mascote_limite_sugestoes' => (int) $config->mascote_limite_sugestoes,
                'lock_expiracao_horas' => (int) $config->lock_expiracao_horas,
                'indicadores_janela_dias' => (int) $config->indicadores_janela_dias,
                'kanban_inatividade_alerta_dias' => (int) $config->kanban_inatividade_alerta_dias,
                'kanban_inatividade_urgente_dias' => (int) $config->kanban_inatividade_urgente_dias,
                'kanban_inatividade_critica_dias' => (int) $config->kanban_inatividade_critica_dias,
            ],
        ]);
    }

    public function salvarConfig(Request $request)
    {
        $empresaId = $this->tenantId();

        $request->validate([
            'dias_sem_contato_reenvio' => 'required|integer|min:1|max:3650',
            'envio_automatico_ativo' => 'required|boolean',
            'limite_envio_diario' => 'required|integer|min:1|max:100000',
            'mascote_dias_sem_atividade' => 'required|integer|min:1|max:3650',
            'mascote_limite_sugestoes' => 'required|integer|min:1|max:100',
            'lock_expiracao_horas' => 'required|integer|min:1|max:168',
            'indicadores_janela_dias' => 'required|integer|min:1|max:3650',
            'kanban_inatividade_alerta_dias' => 'required|integer|min:1|max:3650',
            'kanban_inatividade_urgente_dias' => 'required|integer|gt:kanban_inatividade_alerta_dias|max:3650',
            'kanban_inatividade_critica_dias' => 'required|integer|gt:kanban_inatividade_urgente_dias|max:3650',
        ]);

        PreditivaConfiguracao::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'dias_sem_contato_reenvio' => $request->dias_sem_contato_reenvio,
                'envio_automatico_ativo' => $request->boolean('envio_automatico_ativo'),
                'limite_envio_diario' => $request->limite_envio_diario,
                'mascote_dias_sem_atividade' => $request->mascote_dias_sem_atividade,
                'mascote_limite_sugestoes' => $request->mascote_limite_sugestoes,
                'lock_expiracao_horas' => $request->lock_expiracao_horas,
                'indicadores_janela_dias' => $request->indicadores_janela_dias,
                'kanban_inatividade_alerta_dias' => $request->kanban_inatividade_alerta_dias,
                'kanban_inatividade_urgente_dias' => $request->kanban_inatividade_urgente_dias,
                'kanban_inatividade_critica_dias' => $request->kanban_inatividade_critica_dias,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Configuracoes salvas com sucesso.']);
    }

    /** Server-side DataTable do historico duravel de envios. */
    public function historicoEnvios(Request $request)
    {
        $empresaId = $this->tenantId();
        $dataTable = $this->validarDataTable($request);

        $base = DB::table('preditiva_envios as e')
            ->join('contatos as c', function ($join) {
                $join->on('c.id', '=', 'e.contato_id')
                    ->on('c.empresa_id', '=', 'e.empresa_id');
            })
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', 'e.enviado_por')
                    ->where(function ($visibility) {
                        $visibility->whereColumn('u.empresa_id', 'e.empresa_id')
                            ->orWhere('u.is_platform_admin', true);
                    });
            })
            ->where('e.empresa_id', $empresaId)
            ->select(
                'e.id',
                'c.nome_cliente',
                'c.cpf',
                'e.origem',
                'e.situacao_origem',
                'e.dias_inativo',
                DB::raw("DATE_FORMAT(e.enviado_em, '%d/%m/%Y %H:%i') as enviado_em"),
                DB::raw("COALESCE(u.name, CASE WHEN e.origem = 'AUTOMATICO' THEN 'Automático' ELSE 'Autor indisponível' END) as enviado_por")
            );

        $recordsTotal = (clone $base)->count();

        $searchValue = $dataTable['search']['value'] ?? '';
        if ($searchValue !== '') {
            $base->where(function ($q) use ($searchValue) {
                $q->where('c.nome_cliente', 'LIKE', "%{$searchValue}%")
                    ->orWhere('c.cpf', 'LIKE', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $start = (int) ($dataTable['start'] ?? 0);
        $length = (int) ($dataTable['length'] ?? 25);
        $data = $base->orderBy('e.enviado_em', 'desc')->offset($start)->limit($length)->get();

        return response()->json([
            'draw' => (int) ($dataTable['draw'] ?? 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function validarDataTable(Request $request): array
    {
        return $request->validate([
            'draw' => ['nullable', 'integer', 'min:0'],
            'start' => ['nullable', 'integer', 'between:0,1000000'],
            'length' => ['nullable', 'integer', 'between:1,100'],
            'search' => ['nullable', 'array'],
            'search.value' => ['nullable', 'string', 'max:160'],
            'order' => ['nullable', 'array'],
            'order.0.column' => ['nullable', 'integer', 'between:0,5'],
            'order.0.dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
    }
}
