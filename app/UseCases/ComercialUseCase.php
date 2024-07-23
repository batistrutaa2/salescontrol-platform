<?php

namespace App\UseCases;

use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use Illuminate\Http\Request;


class ComercialUseCase
{
  public function __construct(
    ContatosRepositoryInterface $contatosRepositoryInterface,
    ContatosCorretoresRepositoryInterface $ContatosCorretoresRepositoryInterface


  ) {
  }


  public function saveDataInfo(string $idMailing, string $telefone1, string $telefone2, string $telefone3, $commen)
  {
  }
}
