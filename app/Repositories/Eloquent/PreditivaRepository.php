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
        return $this->model::create([
          'empresa_id' => $data['empresa_id'],
          'contato_id' => $data['contato_id'],
          'status' => $data['status'],
        ]);
      } catch (\Exception $e) {
        return false;
      }
    }

    public function getNextCLient(string $empresa_id)
    {

    }
}
