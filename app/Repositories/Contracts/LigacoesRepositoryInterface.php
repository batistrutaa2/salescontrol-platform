<?php

namespace App\Repositories\Contracts;

interface LigacoesRepositoryInterface
{
  public function create(array $data);

  public function getLigacoes($id_user, $data_inicial, $data_final);
}
