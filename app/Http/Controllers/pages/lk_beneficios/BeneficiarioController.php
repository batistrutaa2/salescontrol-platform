<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Models\Contrato;
use App\Modules\LkBeneficios\Repositories\Contracts\BeneficiarioRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BeneficiarioController extends Controller
{
    public function __construct(private BeneficiarioRepositoryInterface $beneficiarios)
    {
    }

    public function index(int $contratoId): JsonResponse
    {
        $this->garantirContrato($contratoId);
        return response()->json($this->beneficiarios->listarPorContrato($contratoId));
    }

    public function store(Request $request, int $contratoId): JsonResponse
    {
        $this->garantirContrato($contratoId);

        $data = $request->validate([
            'tipo' => 'required|in:TITULAR,DEPENDENTE',
            'nome' => 'required|string|max:150',
            'cpf' => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'grau_parentesco' => 'nullable|string|max:40',
            'telefone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'data_inclusao' => 'nullable|date',
        ]);

        $beneficiario = $this->beneficiarios->incluir($contratoId, $data, Auth::id());

        return response()->json([
            'message' => 'Beneficiário incluído.',
            'beneficiario' => $beneficiario,
        ], 201);
    }

    public function destroy(Request $request, int $contratoId, int $id): JsonResponse
    {
        $this->garantirContrato($contratoId);

        $this->beneficiarios->excluir(
            $id,
            $contratoId,
            Auth::id(),
            $request->input('motivo')
        );

        return response()->json(['message' => 'Beneficiário excluído.']);
    }

    private function garantirContrato(int $contratoId): void
    {
        $existe = Contrato::where('id', $contratoId)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->exists();

        abort_unless($existe, 404);
    }
}
