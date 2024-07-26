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

  public function getCommentsMailing($contato_id)
  {
    return $this->model->where('contato_id', $contato_id)->orderBy('created_at', 'desc')->get();
  }

  public function getCommentsMailingAll($contato_id)
  {
    $comentarios = $this->model->leftJoin('users', 'users.id', '=', 'comentarios.user_id')
      ->leftJoin('user_roles', 'users.user_role_id', '=', 'user_roles.id')
      ->where('comentarios.contato_id', $contato_id)
      ->select(
        'comentarios.anotacao',
        'comentarios.created_at',
        'users.name',
        'user_roles.tipo_usuario'
      )
      ->get();

    return $comentarios;
  }
}
