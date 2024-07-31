<?php

namespace App\Repositories\Contracts;

interface UsuariosRepositoryInterface
{
  public function  usersAccordingToPermission(string $rule, string $idCompany, string $idUser);
  public function  create(array $data);
  public function  all();
  public function  getUserByCompany($id);
  public function getUsersFilterType($empresa_id, $rule);
}
