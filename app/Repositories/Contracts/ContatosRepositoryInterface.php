<?php

namespace App\Repositories\Contracts;

interface ContatosRepositoryInterface
{
  public function create(array $data);
  public function all();
  public function find($id);
  public function searchForCpfsFound(array $cpfs);
}
