<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Services\Comercial\PropostaEnriquecimentoService;
use App\Support\DocumentoFiscal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PropostaConsultaDocumentoController extends Controller
{
    public function __invoke(Request $request, PropostaEnriquecimentoService $service): JsonResponse
    {
        $request->validate(['documento' => ['required', 'string', function ($attribute, $value, $fail) {
            if (! DocumentoFiscal::valido($value)) {
                $fail('Informe um CPF ou CNPJ válido.');
            }
        }]]);

        try {
            return response()->json($service->consultar($request->string('documento')->toString()));
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível consultar o documento agora. Continue preenchendo manualmente.',
            ], 503);
        }
    }
}
