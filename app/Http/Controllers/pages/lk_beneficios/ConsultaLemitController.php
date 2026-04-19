<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Services\LemitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultaLemitController extends Controller
{
    public function __construct(private LemitService $lemit)
    {
    }

    public function cpf(Request $request): JsonResponse
    {
        $request->validate(['cpf' => 'required|string']);

        try {
            return response()->json($this->lemit->consultarCpf($request->cpf));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Falha ao consultar CPF', 'message' => $e->getMessage()], 500);
        }
    }

    public function cnpj(Request $request): JsonResponse
    {
        $request->validate(['cnpj' => 'required|string']);

        try {
            return response()->json($this->lemit->consultarCnpj($request->cnpj));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Falha ao consultar CNPJ', 'message' => $e->getMessage()], 500);
        }
    }
}
