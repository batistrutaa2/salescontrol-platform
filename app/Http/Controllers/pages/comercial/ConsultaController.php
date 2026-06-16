<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Services\ConsultaService;
use Illuminate\Http\Request;

/**
 * Endpoints de enriquecimento. Delegam ao {@see ConsultaService}, que roteia a
 * fonte (Lemit x Assertiva) e serve do banco quando o dado já existe (cache-first).
 */
class ConsultaController extends Controller
{
    public function __construct(private ConsultaService $consulta) {}

    public function consultarPessoa(Request $request)
    {
        $request->validate([
            'cpf' => 'required|string|size:11',
            'fonte' => 'nullable|in:lemit,assertiva',
        ]);

        return $this->responder(fn () => $this->consulta->consultarDocumento($request->cpf, $request->input('fonte', 'lemit')), 'CPF');
    }

    public function consultarEmpresa(Request $request)
    {
        $request->validate([
            'cnpj' => 'required|string|size:14',
            'fonte' => 'nullable|in:lemit,assertiva',
        ]);

        return $this->responder(fn () => $this->consulta->consultarDocumento($request->cnpj, $request->input('fonte', 'lemit')), 'CNPJ');
    }

    public function consultarTelefone(Request $request)
    {
        $request->validate(['telefone' => 'required|string|min:10']);

        return $this->responder(fn () => $this->consulta->consultarTelefone($request->telefone), 'telefone');
    }

    public function consultarEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        return $this->responder(fn () => $this->consulta->consultarEmail($request->email), 'e-mail');
    }

    public function consultarNomeEndereco(Request $request)
    {
        $request->validate([
            'buscarPor' => 'required|string',
            'nomeOuRazaoSocial' => 'nullable|string',
            'uf' => 'nullable|string|size:2',
            'cidade' => 'nullable|string',
            'bairro' => 'nullable|string',
            'cepOuNomeRua' => 'nullable|string',
        ]);

        return $this->responder(
            fn () => $this->consulta->consultarNomeEndereco($request->only([
                'buscarPor', 'nomeOuRazaoSocial', 'nomeOuRazaoSocialExata', 'sexo',
                'dataNascimentoOuAbertura', 'uf', 'cidade', 'bairro', 'cepOuNomeRua',
                'numeroInicial', 'numeroFinal', 'complemento',
            ])),
            'nome/endereço'
        );
    }

    private function responder(callable $callback, string $rotulo)
    {
        try {
            return response()->json($callback());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Erro ao consultar '.$rotulo,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
