<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Exception;

class UsuariosRepository implements UsuariosRepositoryInterface
{
  protected $model;

  public function __construct(User $model)
  {
    $this->model = $model;
  }


  public function all()
  {
    return $this->model->all();
  }

  public function getUserByCompany($id)
  {
    return $this->model->where('empresa_id', $id)->whereNotIn('user_role_id', [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE])->get();
  }

  public function usersAccordingToPermission(string $rule, string $idCompany, string $idUser)
  {
    if ($rule == UserRole::DEVELOPER) {
      return $this->model
        ->where('users.id', '!=', $idUser)
        ->join('user_roles', 'users.user_role_id', '=', 'user_roles.id')
        ->select('users.*', 'user_roles.tipo_usuario')
        ->get();
    } else {
      return $this->model
        ->where('empresa_id', $idCompany)
        ->where('users.id', '!=', $idUser)
        ->join('user_roles', 'users.user_role_id', '=', 'user_roles.id')
        ->select('users.id', 'users.name', 'users.email', 'user_roles.tipo_usuario', 'users.ativo', 'user_roles.created_at')
        ->get();
    }
  }

  public function create(array $data)
  {
    try {
      $this->model->create([
        'name' => $data['name'],
        'email' => $data['email'],
        'user_role_id' => $data['user_role_id'],
        'empresa_id' => $data['empresa_id'],
        'password' => Hash::make($data['password']),
      ]);
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  public function getUsersFilterType($empresa_id, $rule)
  {
    return $this->model->select('id', 'name')->where('empresa_id', $empresa_id)->where('user_role_id', $rule)->get();
  }

  public function getTypeUser($id)
  {
    return $this->model->select('user_role_id')->where('id', $id)->first();
  }
}
