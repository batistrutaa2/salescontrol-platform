<?php

namespace App\Repositories\Contracts;

interface TransferenciaContatoRepositoryInterface
{
  public function saveTransfer($empresa_id, $contato_id, $fromUser, $toUser, $reponsableSend): bool;
  public function monthlyTransferCount($empresa_id);

}
