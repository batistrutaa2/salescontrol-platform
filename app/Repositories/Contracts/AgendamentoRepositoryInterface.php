<?php

namespace App\Repositories\Contracts;

interface AgendamentoRepositoryInterface
{
  public function updateOrCreate($contato_id, $horario_agendamento, $obs);
}
