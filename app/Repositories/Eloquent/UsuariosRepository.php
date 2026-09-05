<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Support\TenantContext;
use Exception;
use Illuminate\Support\Facades\Hash;

class UsuariosRepository implements UsuariosRepositoryInterface
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->where('empresa_id', app(TenantContext::class)->id())->get();
    }

    public function getUserByCompany($id)
    {
        return $this->model
            ->where('empresa_id', $id)
            ->where('ativo', 'Y')
            ->whereNotIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::BACKOFFICE])
            ->orderBy('name')
            ->get();
    }

    public function usersAccordingToPermission(string $rule, string $idCompany, string $idUser)
    {
        return $this->model
            ->where('empresa_id', $idCompany)
            ->where('users.id', '!=', $idUser)
            ->where('users.is_platform_admin', false)
            ->when((int) $rule !== UserRole::DEVELOPER, fn ($query) => $query->where('users.user_role_id', '!=', UserRole::DEVELOPER))
            ->join('user_roles', 'users.user_role_id', '=', 'user_roles.id')
            ->select('users.id', 'users.name', 'users.email', 'user_roles.tipo_usuario', 'users.ativo', 'users.created_at')
            ->get();
    }

    public function create(array $data)
    {
        try {
            $this->model->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'user_role_id' => $data['user_role_id'],
                'is_platform_admin' => (int) $data['user_role_id'] === UserRole::DEVELOPER,
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
        return $this->model->select('user_role_id')
            ->where('id', $id)
            ->where('empresa_id', app(TenantContext::class)->id())
            ->first();
    }

    public function getUserSearchName($nameUser)
    {
        return $this->model::select('id', 'empresa_id', 'user_role_id')
            ->where('name', $nameUser)
            ->where('empresa_id', app(TenantContext::class)->id())
            ->first();
    }

    public function find($id)
    {
        return $this->model::where('empresa_id', app(TenantContext::class)->id())->find($id);
    }

    public function editUser(array $data, int $empresaId): bool
    {
        return $this->model::query()
            ->whereKey($data['user_id'])
            ->where('empresa_id', $empresaId)
            ->update([
                'name' => $data['name'],
                'ativo' => $data['ativo'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'birthdate' => $data['birthdate'] ?? null,
            ]) > 0;
    }

    public function updatePassword(int $userId, int $empresaId, string $senha): bool
    {
        return $this->model::where('id', $userId)
            ->where('empresa_id', $empresaId)
            ->update(['password' => bcrypt($senha)]) > 0;
    }
}
