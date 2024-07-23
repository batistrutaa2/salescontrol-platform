<?php

namespace App\Repositories\Contracts;

interface ComentariosRepositoryInterface
{
  public function createComment($empresa_id, $user_id, $comment,  $contato_id);
}
