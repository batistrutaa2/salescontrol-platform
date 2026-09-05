<?php

namespace App\Http\Controllers\pages\manager;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Support\DocumentoFiscal;
use App\UseCases\EmpresaUseCase;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Empresa extends Controller
{
    protected EmpresaRepositoryInterface $empresaRepository;

    protected EmpresaUseCase $useCaseEmpresa;

    public function __construct(EmpresaRepositoryInterface $empresaRepository)
    {
        $this->empresaRepository = $empresaRepository;
        $this->useCaseEmpresa = new EmpresaUseCase($empresaRepository);
    }

    public function index()
    {
        return view('content.pages.manager.empresa');
    }

    public function getAllCompanies(): JsonResponse
    {
        $empresas = $this->empresaRepository->getCompanies(['id', 'nome_fantasia', 'cpf_cnpj', 'telefone', 'email', 'created_at']);

        return response()->json($empresas);
    }

    public function createCompanies(Request $request): JsonResponse
    {
        $documento = DocumentoFiscal::somenteDigitos($request->input('cpf_cnpj'));
        $telefone = preg_replace('/\D+/', '', (string) $request->input('telefone'));
        $payload = [
            'nome_fantasia' => trim((string) $request->input('nome_fantasia')),
            'cpf_cnpj' => $documento,
            'cpf_cnpj_normalizado' => $documento,
            'telefone' => $telefone,
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ];
        $validator = Validator::make($payload, [
            'nome_fantasia' => ['required', 'string', 'max:255'],
            'cpf_cnpj' => [
                'required',
                'string',
                'max:14',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! DocumentoFiscal::valido((string) $value)) {
                        $fail('Informe um CPF ou CNPJ válido.');
                    }
                },
            ],
            'cpf_cnpj_normalizado' => ['required', Rule::unique('empresas', 'cpf_cnpj_normalizado')],
            'telefone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ], [
            'cpf_cnpj_normalizado.unique' => 'Já existe uma empresa cadastrada com este CPF ou CNPJ.',
            'telefone.regex' => 'Informe um telefone com DDD válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresa = $this->useCaseEmpresa->createCompany($validator->validated());
        } catch (QueryException $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => 'Não foi possível criar a empresa. Verifique se os dados já estão cadastrados.',
            ], 409);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'error' => true,
                'message' => 'Não foi possível criar a empresa agora. Tente novamente.',
            ], 500);
        }

        return response()->json([
            'error' => false,
            'message' => 'Empresa criada com sucesso.',
            'empresa_id' => $empresa->id,
        ], 201);
    }
}
