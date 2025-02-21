<?php

namespace App\Repositories\Contracts;

interface EmpresaRepositoryInterface
{
  public function getCompanies(array $data);
  public function create(array $data);
  public function all();
  public function find($id);
}
