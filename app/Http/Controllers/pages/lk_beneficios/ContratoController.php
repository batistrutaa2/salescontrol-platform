<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Enums\StatusContrato;
use App\Modules\LkBeneficios\Enums\TipoBeneficio;
use App\Modules\LkBeneficios\Models\Produto;
use App\Modules\LkBeneficios\Repositories\Contracts\ContratoRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ContratoController extends Controller
{
    public function __construct(private ContratoRepositoryInterface $contratos)
    {
    }

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $produtos = Produto::where('empresa_id', $empresaId)->where('ativo', true)->orderBy('nome')->get();

        return view('content.pages.lk-beneficios.contratos.index', [
            'produtos' => $produtos,
            'statusList' => StatusContrato::all(),
            'tiposList' => TipoBeneficio::all(),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;
        $query = $this->contratos->queryForDataTable($empresaId, $request->only(['status', 'tipo', 'busca']));

        return DataTables::of($query)
            ->addColumn('produto_nome', fn ($row) => $row->produto?->nome ?? '—')
            ->addColumn('produto_tipo_label', fn ($row) => $row->produto ? TipoBeneficio::label($row->produto->tipo) : '—')
            ->addColumn('status_label', fn ($row) => StatusContrato::label($row->status))
            ->addColumn('status_class', fn ($row) => StatusContrato::badgeClass($row->status))
            ->addColumn('corretor_nome', fn ($row) => $row->user?->name ?? '—')
            ->addColumn('valor_mensal_fmt', fn ($row) => 'R$ ' . number_format((float) $row->valor_mensal, 2, ',', '.'))
            ->addColumn('data_vigencia_fmt', fn ($row) => $row->data_vigencia_inicio
                ? Carbon::parse($row->data_vigencia_inicio)->format('d/m/Y')
                : '—')
            ->make(true);
    }

    public function show(int $id)
    {
        $empresaId = Auth::user()->empresa_id;
        $contrato = $this->contratos->findByEmpresa($id, $empresaId);

        if (! $contrato) {
            abort(404);
        }

        return view('content.pages.lk-beneficios.contratos.show', compact('contrato'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['empresa_id'] = Auth::user()->empresa_id;
        $data['user_id'] = Auth::id();

        $contrato = $this->contratos->create($data);

        return response()->json([
            'message' => 'Contrato criado com sucesso.',
            'contrato' => $contrato,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validatePayload($request, forUpdate: true);
        $empresaId = Auth::user()->empresa_id;

        $contrato = $this->contratos->update($id, $empresaId, $data);

        return response()->json([
            'message' => 'Contrato atualizado.',
            'contrato' => $contrato,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;
        $this->contratos->delete($id, $empresaId);

        return response()->json(['message' => 'Contrato removido.']);
    }

    private function validatePayload(Request $request, bool $forUpdate = false): array
    {
        $rule = $forUpdate ? 'sometimes|' : 'required|';

        return $request->validate([
            'produto_id' => $rule . 'integer|exists:lk_beneficios_produtos,id',
            'cliente_tipo' => $rule . 'in:PF,PJ',
            'cpf_cnpj' => $rule . 'string|max:20',
            'nome_cliente' => $rule . 'string|max:150',
            'status' => 'sometimes|in:' . implode(',', StatusContrato::all()),
            'numero_apolice' => 'nullable|string|max:60',
            'numero_proposta' => 'nullable|string|max:60',
            'data_proposta' => 'nullable|date',
            'data_vigencia_inicio' => 'nullable|date',
            'data_vigencia_fim' => 'nullable|date|after_or_equal:data_vigencia_inicio',
            'data_aniversario_reajuste' => 'nullable|date',
            'valor_mensal' => 'nullable|numeric|min:0',
            'valor_anual' => 'nullable|numeric|min:0',
            'forma_pagamento' => 'nullable|in:MENSAL,TRIMESTRAL,SEMESTRAL,ANUAL',
            'dia_vencimento' => 'nullable|integer|min:1|max:31',
            'vidas_total' => 'nullable|integer|min:0',
            'vidas_titulares' => 'nullable|integer|min:0',
            'vidas_dependentes' => 'nullable|integer|min:0',
            'comissao_percentual_primeira' => 'nullable|numeric|min:0|max:100',
            'comissao_percentual_recorrente' => 'nullable|numeric|min:0|max:100',
            'observacoes' => 'nullable|string',
            'detalhes_vida' => 'nullable|array',
            'detalhes_previdencia' => 'nullable|array',
            'detalhes_patrimonial' => 'nullable|array',
        ]);
    }
}
