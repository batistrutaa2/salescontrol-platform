<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Models\PreditivaConfiguracao;
use App\Repositories\Eloquent\ContatosRepository;
use App\Services\ReciclagemLeadsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReciclagemLeads extends Controller
{
    public function __construct(
        private ContatosRepository $contatosRepository,
        private ReciclagemLeadsService $reciclagemService
    ) {
    }

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $config    = PreditivaConfiguracao::getOrDefault($empresaId);
        $resumo    = $this->contatosRepository->getResumoReciclagem($empresaId, (int) $config->dias_sem_contato_reenvio);

        return view('content.pages.comercial.reciclagem-leads', compact('resumo', 'config'));
    }

    /** Server-side DataTable dos leads frios elegiveis. */
    public function getElegiveis(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $dias      = (int) PreditivaConfiguracao::getOrDefault($empresaId)->dias_sem_contato_reenvio;

        return response()->json($this->contatosRepository->getLeadsFriosElegiveis($empresaId, $dias, $request));
    }

    /** Recalcula os KPIs dos baldes (chamado apos envios / mudanca de config). */
    public function resumo()
    {
        $empresaId = Auth::user()->empresa_id;
        $dias      = (int) PreditivaConfiguracao::getOrDefault($empresaId)->dias_sem_contato_reenvio;

        return response()->json([
            'success' => true,
            'resumo'  => $this->contatosRepository->getResumoReciclagem($empresaId, $dias),
        ]);
    }

    /** Envio manual: ids selecionados, ou todos os elegiveis (todos=true). */
    public function enviar(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $todos     = $request->boolean('todos');
        $ids       = $request->input('ids');

        $resultado = $this->reciclagemService->enviarElegiveisEmLote(
            $empresaId,
            $todos ? null : (array) $ids,
            'MANUAL',
            Auth::id(),
            null
        );

        return response()->json([
            'success'  => true,
            'message'  => "{$resultado['enviados']} lead(s) enviados para a preditiva.",
            'resultado' => $resultado,
        ]);
    }

    public function getConfig()
    {
        $empresaId = Auth::user()->empresa_id;
        $config    = PreditivaConfiguracao::getOrDefault($empresaId);

        return response()->json([
            'success' => true,
            'config'  => [
                'dias_sem_contato_reenvio' => (int) $config->dias_sem_contato_reenvio,
                'envio_automatico_ativo'   => (bool) $config->envio_automatico_ativo,
                'limite_envio_diario'      => (int) $config->limite_envio_diario,
            ],
        ]);
    }

    public function salvarConfig(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $request->validate([
            'dias_sem_contato_reenvio' => 'required|integer|min:1|max:3650',
            'envio_automatico_ativo'   => 'required|boolean',
            'limite_envio_diario'      => 'required|integer|min:1|max:100000',
        ]);

        PreditivaConfiguracao::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'dias_sem_contato_reenvio' => $request->dias_sem_contato_reenvio,
                'envio_automatico_ativo'   => $request->boolean('envio_automatico_ativo'),
                'limite_envio_diario'      => $request->limite_envio_diario,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Configuracoes salvas com sucesso.']);
    }

    /** Server-side DataTable do historico duravel de envios. */
    public function historicoEnvios(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $base = DB::table('preditiva_envios as e')
            ->join('contatos as c', 'c.id', '=', 'e.contato_id')
            ->leftJoin('users as u', 'u.id', '=', 'e.enviado_por')
            ->where('e.empresa_id', $empresaId)
            ->select(
                'e.id',
                'c.nome_cliente',
                'c.cpf',
                'e.origem',
                'e.situacao_origem',
                'e.dias_inativo',
                DB::raw("DATE_FORMAT(e.enviado_em, '%d/%m/%Y %H:%i') as enviado_em"),
                DB::raw("COALESCE(u.name, 'Automatico') as enviado_por")
            );

        $recordsTotal = (clone $base)->count();

        $searchValue = $request->input('search.value', '');
        if ($searchValue !== '') {
            $base->where(function ($q) use ($searchValue) {
                $q->where('c.nome_cliente', 'LIKE', "%{$searchValue}%")
                    ->orWhere('c.cpf', 'LIKE', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $base)->count();

        $start  = $request->input('start', 0);
        $length = $request->input('length', 25);
        $data = $base->orderBy('e.enviado_em', 'desc')->offset($start)->limit($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }
}
