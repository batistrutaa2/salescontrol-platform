<?php

namespace App\Repositories\Contracts;

interface RamaisRepositoryInterface
{
  public function create(array $data);

  public function getRamais($typeUser, $empresa_id);
}
