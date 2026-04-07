<?php

namespace App\Repositories\Eloquent;

use App\Models\LogPreditiva;
use App\Models\Preditiva;
use App\Repositories\Contracts\LogPreditivaRepositoryInterface;

class LogPreditivaRepository implements LogPreditivaRepositoryInterface
{
  protected LogPreditiva $model;

  public function __construct(LogPreditiva $model)
  {
    $this->model = $model;
  }
    public function create(array $data)
    {
      try {
        return $this->model->create([
          'empresa_id'    => $data['empresa_id'],
          'user_id'       => $data['user_id'],
          'contato_id'    => $data['contato_id'],
          'tabulacao'     => $data['tabulacao'],
          'acao'          => $data['acao'],
          'tipo_descarte' => $data['tipo_descarte'] ?? null,
        ]);
      }catch (\Exception){
        return false;
      }
    }
}
