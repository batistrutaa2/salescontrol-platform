<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Models\Operadora;
use App\Modules\LkBeneficios\Enums\TipoBeneficio;
use App\Modules\LkBeneficios\Repositories\Contracts\ProdutoRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class ProdutoController extends Controller
{
    public function __construct(private ProdutoRepositoryInterface $produtos)
    {
    }

    public function index()
    {
        $tipos = collect(TipoBeneficio::all())->map(fn ($t) => [
            'value' => $t,
            'label' => TipoBeneficio::label($t),
            'icon' => TipoBeneficio::icon($t),
        ])->values();

        $operadoras = Operadora::orderBy('nome')->get(['id', 'nome']);

        return view('content.pages.lk-beneficios.produtos.index', [
            'tipos' => $tipos,
            'operadoras' => $operadoras,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;
        $query = $this->produtos->queryForDataTable(
            $empresaId,
            $request->only(['tipo', 'status', 'busca'])
        );

        return DataTables::of($query)
            ->addColumn('tipo_label', fn ($row) => TipoBeneficio::label($row->tipo))
            ->addColumn('tipo_icon', fn ($row) => TipoBeneficio::icon($row->tipo))
            ->addColumn('operadora_nome', fn ($row) => $row->operadora?->nome)
            ->addColumn('status_label', fn ($row) => $row->ativo ? 'Ativo' : 'Inativo')
            ->addColumn('created_at_fmt', fn ($row) => $row->created_at?->format('d/m/Y'))
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['empresa_id'] = Auth::user()->empresa_id;

        $produto = $this->produtos->create($data);

        return response()->json([
            'message' => 'Produto criado com sucesso.',
            'produto' => $produto->load('operadora:id,nome'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $this->validatePayload($request, forUpdate: true);
        $empresaId = Auth::user()->empresa_id;

        $produto = $this->produtos->findByEmpresa($id, $empresaId);
        if (! $produto) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }

        if (
            isset($data['tipo'])
            && $data['tipo'] !== $produto->tipo
            && $this->produtos->temContratosVinculados($id, $empresaId)
        ) {
            return response()->json([
                'message' => 'Não é possível alterar o tipo de um produto que já possui contratos vinculados.',
            ], 422);
        }

        $produto = $this->produtos->update($id, $empresaId, $data);

        return response()->json([
            'message' => 'Produto atualizado.',
            'produto' => $produto,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;

        $produto = $this->produtos->findByEmpresa($id, $empresaId);
        if (! $produto) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }

        if ($this->produtos->temContratosVinculados($id, $empresaId)) {
            return response()->json([
                'message' => 'Existem contratos vinculados a este produto. Desative-o em vez de excluir.',
            ], 422);
        }

        $this->produtos->delete($id, $empresaId);

        return response()->json(['message' => 'Produto removido.']);
    }

    public function toggleAtivo(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['ativo' => 'required|boolean']);
        $empresaId = Auth::user()->empresa_id;

        $produto = $this->produtos->findByEmpresa($id, $empresaId);
        if (! $produto) {
            return response()->json(['message' => 'Produto não encontrado.'], 404);
        }

        $produto = $this->produtos->setAtivo($id, $empresaId, (bool) $data['ativo']);

        return response()->json([
            'message' => $produto->ativo ? 'Produto ativado.' : 'Produto desativado.',
            'ativo' => $produto->ativo,
        ]);
    }

    private function validatePayload(Request $request, bool $forUpdate = false): array
    {
        $rule = $forUpdate ? 'sometimes|' : 'required|';

        return $request->validate([
            'nome' => $rule . 'string|max:120',
            'tipo' => [trim($rule, '|'), Rule::in(TipoBeneficio::all())],
            'subtipo' => 'nullable|string|max:80',
            'operadora_id' => 'nullable|integer|exists:operadoras,id',
            'descricao' => 'nullable|string|max:1000',
            'ativo' => 'sometimes|boolean',
        ]);
    }
}
