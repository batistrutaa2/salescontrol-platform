<?php

namespace App\Repositories\Eloquent;


use App\Models\Agendamento;
use App\Repositories\Contracts\AgendamentoRepositoryInterface;

class AgendamentoRepository implements AgendamentoRepositoryInterface
{
  protected $model;

  public function __construct(Agendamento $model)
  {
    $this->model = $model;
  }

}
