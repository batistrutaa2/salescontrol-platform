<?php

namespace App\Repositories\Eloquent;


use App\Enums\UserRole;
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

  public function getSchedules($rulerUser)
  {
    try {
      if ($rulerUser == UserRole::ADMINISTRATIVO || $rulerUser == UserRole::DEVELOPER) {
        return $this->model->select('c.id', 'b.name AS nome_corretor', 'c.nome_cliente', 'agendamentos.horario_agendamento', 'agendamentos.notificado')
          ->leftJoin('users AS b', 'b.id', '=', 'agendamentos.user_id')
          ->leftJoin('contatos AS c', 'c.id', '=', 'agendamentos.contato_id')
          ->where('agendamentos.empresa_id', Auth::user()->empresa_id)
          ->get();
      } else {
        return $this->model->select('c.id', 'b.name AS nome_corretor', 'c.nome_cliente', 'agendamentos.horario_agendamento', 'agendamentos.notificado')
          ->leftJoin('users AS b', 'b.id', '=', 'agendamentos.user_id')
          ->leftJoin('contatos AS c', 'c.id', '=', 'agendamentos.contato_id')
          ->where('agendamentos.empresa_id', Auth::user()->empresa_id)
          ->where('b.id', Auth::user()->id)
          ->get();
      }
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
