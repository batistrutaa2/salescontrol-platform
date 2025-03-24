<?php

namespace App\Repositories\Eloquent;

use App\Models\TransferenciaContato;
use App\Repositories\Contracts\TransferenciaContatoRepositoryInterface;


class TransferenciaContatoRepository implements TransferenciaContatoRepositoryInterface
{
  protected $model;

  public function __construct(TransferenciaContato $model)
  {
    $this->model = $model;
  }

  public function saveTransfer($empresa_id, $contato_id, $fromUser, $toUser, $reponsableSend): bool
  {
      return (bool) $this->model::create([
          'empresa_id' => $empresa_id,
          'contato_id' => $contato_id,
          'para_user_id' => $toUser, // Corrigido: `para_user_id` deveria ser `$toUser`
          'de_users_id' => $fromUser, // Corrigido: `de_users_id` deveria ser `$fromUser`
          'responsavel_transferencia' => $reponsableSend
      ]);
  }


  public function monthlyTransferCount($empresa_id) {

  }
}
