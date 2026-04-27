<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Models\Produto;
use App\Modules\LkBeneficios\Repositories\Contracts\BaseSaudeRepositoryInterface;
use App\Modules\LkBeneficios\Services\AquisicaoLeadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class BaseSaudeController extends Controller
{
    public function __construct(
        private BaseSaudeRepositoryInterface $baseSaude,
        private AquisicaoLeadService $aquisicao,
    ) {
    }

    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $produtos = Produto::where('empresa_id', $empresaId)->where('ativo', true)->orderBy('nome')->get();

        return view('content.pages.lk-beneficios.base-saude.index', compact('produtos'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $empresaId = Auth::user()->empresa_id;
        $filtros = [
            'busca' => $request->input('busca'),
            'ocultar_com_lead' => $request->boolean('ocultar_com_lead'),
            'ocultar_com_contrato' => $request->boolean('ocultar_com_contrato'),
        ];

        $query = $this->baseSaude->queryClientesParaAquisicao($empresaId, $filtros);

        return DataTables::of($query)
            ->addColumn('primeira_implantacao_fmt', fn ($row) => $row->primeira_implantacao
                ? Carbon::parse($row->primeira_implantacao)->format('d/m/Y')
                : '—')
            ->addColumn('ultima_implantacao_fmt', fn ($row) => $row->ultima_implantacao
                ? Carbon::parse($row->ultima_implantacao)->format('d/m/Y')
                : '—')
            ->addColumn('tem_lead', fn ($row) => (int) $row->leads_ativos > 0)
            ->addColumn('tem_contrato', fn ($row) => (int) $row->contratos_beneficios > 0)
            ->make(true);
    }

    public function pegar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cpf_cnpj' => 'required|string|max:20',
            'produto_id' => 'required|integer|exists:lk_beneficios_produtos,id',
        ]);

        try {
            $lead = $this->aquisicao->pegarDaBaseSaude(
                $data['cpf_cnpj'],
                (int) $data['produto_id'],
                Auth::user()->empresa_id,
                Auth::id(),
            );

            return response()->json([
                'message' => 'Lead criado. Card em NOVO CLIENTE.',
                'lead_id' => $lead->id,
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
