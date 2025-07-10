<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\Comentarios;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contracts\ComentariosRepositoryInterface;

class ComentariosRepository implements ComentariosRepositoryInterface
{

  protected $model;

  public function __construct(Comentarios $model)
  {
    $this->model = $model;
  }

  public function clearComments(array $data)
  {
    try {
      $leadIds = explode(',', $data['selectedLeadIds']);
      array_map(function ($leadId) use ($data) {
        $this->model->where('contato_id', $leadId)->update([
          "visivel" => "N"
        ]);
      }, $leadIds);
      return true;
    } catch (\Throwable $th) {
      return false;
    }
  }


  public function clearCommentsOne($idMailing)
  {
    try {
      $this->model->where('contato_id', $idMailing)->update([
        "visivel" => "N"
      ]);
      return true;
    } catch (\Throwable $th) {
      return false;
    }
  }


  public function createComment($empresa_id, $user_id, $comment, $contato_id)
  {
    try {
      $supervisao = "N";
      if (Auth::user()->user_role_id != UserRole::VENDEDOR) {
        $supervisao = "Y";
      }

      $commentModel = new $this->model;
      $commentModel->empresa_id = $empresa_id;
      $commentModel->user_id = $user_id;
      $commentModel->contato_id = $contato_id;
      $commentModel->anotacao = $comment;
      $commentModel->supervisao = $supervisao;
      return $commentModel->save();
    } catch (\Throwable $th) {
      return false;
    }
  }

  public function getCommentsMailing($contato_id)
  {
    $user = Auth::user();
    return $this->model->where('contato_id', $contato_id)
      ->when($user->user_role_id == UserRole::VENDEDOR, function ($query) use ($user) {
        $query->where(function ($subQuery) use ($user) {
          $subQuery->where(function ($q) use ($user) {
            $q->where('comentarios.visivel', 'Y')
              ->where('comentarios.user_id', $user->id);
          })
            ->orWhere(function ($q) {
              $q->where('comentarios.supervisao', 'Y')
                ->where('comentarios.visivel', 'Y');
            });
        });
      })
      ->orderBy('created_at', 'desc')->get();
  }

  public function getCommentsMailingAll($contato_id)
  {
    $user = Auth::user();

    $comentarios = $this->model->leftJoin('users', 'users.id', '=', 'comentarios.user_id')
      ->leftJoin('user_roles', 'users.user_role_id', '=', 'user_roles.id')
      ->where('comentarios.contato_id', $contato_id)
      ->when($user->user_role_id == UserRole::VENDEDOR, function ($query) use ($user) {
        $query->where(function ($subQuery) use ($user) {
          $subQuery->where(function ($q) use ($user) {
            $q->where('comentarios.visivel', 'Y')
              ->where('comentarios.user_id', $user->id);
          })
            ->orWhere(function ($q) {
              $q->where('comentarios.supervisao', 'Y')
                ->where('comentarios.visivel', 'Y');
            });
        });
      })
      ->select(
        'comentarios.anotacao',
        'comentarios.created_at',
        'users.name',
        'user_roles.tipo_usuario'
      )
      ->orderBy('comentarios.created_at', 'desc')
      ->get();

    return $comentarios;
  }




}
