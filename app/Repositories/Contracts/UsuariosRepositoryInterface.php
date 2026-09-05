<?php

namespace App\Repositories\Contracts;

interface UsuariosRepositoryInterface
{
    public function usersAccordingToPermission(string $rule, string $idCompany, string $idUser);

    public function create(array $data);

    public function all();

    public function getUserByCompany($id);

    public function getUsersFilterType($empresa_id, $rule);

    public function getTypeUser($id);

    public function getUserSearchName($user_name);

    public function find($id);

    public function editUser(array $data, int $empresaId): bool;

    public function updatePassword(int $userId, int $empresaId, string $senha): bool;
}
