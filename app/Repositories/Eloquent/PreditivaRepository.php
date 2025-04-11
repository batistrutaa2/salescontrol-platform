<?php

namespace App\Repositories\Eloquent;

use App\Models\Empresa;
use App\Models\Ligacoes;
use App\Models\Preditiva;
use App\Repositories\Contracts\PreditivaRepositoryInterface;

class PreditivaRepository implements PreditivaRepositoryInterface
{

  protected Preditiva $model;

  public function __construct(Preditiva $model)
  {
    $this->model = $model;
  }

  public function create(array $data): bool
  {
      try {
          $result = $this->model::create($data);
          return $result !== null;
      } catch (\Exception $e) {
          return false;
      }
  }

    public function getNextCLient(string $empresa_id)
    {

    }
}
