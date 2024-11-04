<?php

namespace App\Repositories\Eloquent;


use App\Models\Agendamento;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contracts\AgendamentoRepositoryInterface;

class AgendamentoRepository implements AgendamentoRepositoryInterface
{
  protected $model;

  public function __construct(Agendamento $model)
  {
    $this->model = $model;
  }

  public function updateOrCreate($contato_id, $horario_agendamento, $obs)
  {
    try {
      $conditions = ['contato_id' => $contato_id];

      $createOrUpdate = $this->model::updateOrCreate(
        $conditions,
        [
          'empresa_id' => Auth::user()->empresa_id,
          'user_id' => Auth::user()->id,
          'contato_id' => $contato_id,
          'horario_agendamento' => $horario_agendamento,
          'observacao' => $obs,
        ]
      );

      return $createOrUpdate;

    } catch (\Throwable $th) {
      return false;
    }
  }
}
