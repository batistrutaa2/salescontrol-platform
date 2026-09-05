<?php

namespace App\Http\Controllers\pages\comercial;

use App\Http\Controllers\Controller;
use App\Services\Enrichment\ConsultaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Endpoints de enriquecimento. Delegam ao {@see ConsultaService}, que roteia a
 * fonte (Lemit x Assertiva). Apenas o cache Assertiva, isolado por empresa, é
 * reutilizado; a Lemit é consultada diretamente para evitar cache legado global.
 */
class ConsultaController extends Controller
{
    public function __construct(private ConsultaService $consulta) {}

    public function consultarPessoa(Request $request)
    {
        $request->validate([
            'cpf' => ['required', 'string', 'regex:/^\d{11}$/'],
            'fonte' => 'nullable|in:lemit,assertiva',
        ]);

        return $this->responder(fn () => $this->consulta->consultarDocumento($request->cpf, $request->input('fonte', 'lemit')), 'CPF');
    }

    public function consultarEmpresa(Request $request)
    {
        $request->validate([
            'cnpj' => ['required', 'string', 'regex:/^\d{14}$/'],
            'fonte' => 'nullable|in:lemit,assertiva',
        ]);

        return $this->responder(fn () => $this->consulta->consultarDocumento($request->cnpj, $request->input('fonte', 'lemit')), 'CNPJ');
    }

    public function consultarTelefone(Request $request)
    {
        $request->validate(['telefone' => ['required', 'string', 'regex:/^\d{10,13}$/']]);

        return $this->responder(fn () => $this->consulta->consultarTelefone($request->telefone), 'telefone');
    }

    public function consultarEmail(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:254']]);

        return $this->responder(fn () => $this->consulta->consultarEmail($request->email), 'e-mail');
    }

    public function consultarNomeEndereco(Request $request)
    {
        $request->validate([
            'buscarPor' => ['required', Rule::in(['pessoaFisica', 'pessoaJuridica', 'ambas'])],
            'nomeOuRazaoSocial' => ['nullable', 'string', 'max:160'],
            'nomeOuRazaoSocialExata' => ['nullable', 'boolean'],
            'sexo' => ['nullable', Rule::in(['M', 'F'])],
            'dataNascimentoOuAbertura' => ['nullable', 'date_format:Y-m-d'],
            'uf' => ['nullable', 'string', 'size:2'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cepOuNomeRua' => ['nullable', 'string', 'max:160'],
            'numeroInicial' => ['nullable', 'integer', 'min:0'],
            'numeroFinal' => ['nullable', 'integer', 'min:0', 'gte:numeroInicial'],
            'complemento' => ['nullable', 'string', 'max:100'],
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
            Log::error('Falha em consulta de enriquecimento.', [
                'tipo' => $rotulo,
                'user_id' => auth()->id(),
                'empresa_id' => $this->tenantId(),
                'exception' => $e,
            ]);

            return response()->json([
                'error' => 'Erro ao consultar '.$rotulo,
                'message' => 'Não foi possível concluir a consulta neste momento.',
            ], 500);
        }
    }
}
