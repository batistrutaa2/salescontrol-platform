<?php

namespace App\Http\Controllers\pages\manager;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use Illuminate\Http\Request;
use App\UseCases\EmpresaUseCase;
use Illuminate\Support\Facades\Validator;

class Empresa extends Controller
{
  private $rulesCreateClient = [
    'nome_fantasia' => 'required|string',
    'cpf_cnpj' => 'required|string',
    'telefone' => 'required|string',
    'email' => 'required|email',
  ];

  protected $empresaRepository;
  protected $useCaseEmpresa;
  public function __construct(EmpresaRepositoryInterface $empresaRepositoryInterface)
  {
    $this->empresaRepository = $empresaRepositoryInterface;
    $this->useCaseEmpresa = new EmpresaUseCase($empresaRepositoryInterface);
  }


  public function index()
  {
    return view('content.pages.manager.empresa');
  }


  public function getAllCompanies()
  {
    $empresas = $this->empresaRepository->getCompanies(['id', 'nome_fantasia', 'cpf_cnpj', 'telefone', 'email', 'created_at']);

    return response()->json($empresas);
  }


  public function createCompanies(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), $this->rulesCreateClient);
      if ($validator->fails()) {
        $firstError = $validator->errors()->first();

        return response()->json([
          'error' => true,
          'message' => $firstError
        ], 422);
      }

      return $this->useCaseEmpresa->createCompanie($request);
    } catch (\Throwable $th) {
      return response()->json([
        'error' => true,
        'message' => "Erro ao criar empresa"
      ]);
    }
  }
}
