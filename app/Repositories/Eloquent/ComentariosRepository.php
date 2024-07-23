<?php

namespace App\Repositories\Eloquent;

use App\Models\Comentarios;
use App\Repositories\Contracts\ComentariosRepositoryInterface;

class ComentariosRepository implements ComentariosRepositoryInterface
{

  protected $model;

  public function __construct(Comentarios $model)
  {
    $this->model = $model;
  }


  public function createComment($empresa_id, $user_id, $comment, $contato_id)
  {
    try {
      $commentModel = new $this->model;
      $commentModel->empresa_id = $empresa_id;
      $commentModel->user_id = $user_id;
      $commentModel->contato_id = $contato_id;
      $commentModel->anotacao = $comment;
      return $commentModel->save();
    } catch (\Throwable $th) {
      return false;
    }
  }
}
