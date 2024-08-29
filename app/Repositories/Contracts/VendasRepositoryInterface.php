<?php

namespace App\Repositories\Contracts;

interface VendasRepositoryInterface
{
  public function  create(array $data);
  public function  all();
  public function vendasDoMesAnoAtual();
}
