<?php

namespace App\UseCases;

use App\Repositories\Contracts\EmpresaRepositoryInterface;
use Illuminate\Http\Request;


class EmpresaUseCase
{
  protected $empresaRepository;

  public function __construct(EmpresaRepositoryInterface $empresaRepository)
  {
    $this->empresaRepository = $empresaRepository;
  }

  public function createCompanie(Request $request)
  {
    $status =  $this->empresaRepository->create($request->all());
    if ($status) {
      return response()->json([
        'error' => false,
        'message' => "Empresa criada com sucesso."
      ]);
    } else {
      return response()->json([
        'error' => true,
        'message' => "Erro ao criar empresa"
      ]);
    }
  }
}
