<?php

namespace App\Http\Controllers\pages\comercial;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ConsultaController extends Controller
{
    private $apiToken = 'fLcajNZ8d9XAB45jqtFTqgxy1lu2UpFHTMCAuTyi';
    private $baseUrl = 'https://api.lemit.com.br/api/v1/consulta';

    public function consultarPessoa(Request $request)
    {

        $request->validate([
            'cpf' => 'required|string|size:11'
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post($this->baseUrl . '/pessoa', [
                'documento' => $request->cpf
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'CPF não encontrado ou erro na consulta'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }

    public function consultarEmpresa(Request $request)
    {
        $request->validate([
            'cnpj' => 'required|string|size:14'
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->post($this->baseUrl . '/empresa', [
                'documento' => $request->cnpj
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'error' => 'CNPJ não encontrado ou erro na consulta'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
