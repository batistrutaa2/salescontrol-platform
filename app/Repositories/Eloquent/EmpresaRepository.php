<?php

namespace App\Repositories\Eloquent;

use App\Models\Empresa;
use App\Repositories\Contracts\EmpresaRepositoryInterface;

class EmpresaRepository implements EmpresaRepositoryInterface
{

  protected $model;

  public function __construct(Empresa $model)
  {
    $this->model = $model;
  }

  public function all()
  {
    return $this->model->all();
  }

  public function find($id)
  {
    return $this->model->find($id);
  }

  public function create(array $data)
  {
    try {
      $empresa = $this->model->create($data);
      return $empresa instanceof Empresa;
    } catch (\Exception $e) {
      return false;
    }
  }

  public function getCompanies($field)
  {
    return $this->model::select($field)->get();
  }
}
