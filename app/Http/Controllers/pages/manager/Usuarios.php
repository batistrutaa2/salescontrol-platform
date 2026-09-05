<?php

namespace App\Http\Controllers\pages\manager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\UseCases\UsuarioUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Usuarios extends Controller
{
    protected $usuariosRepository;

    protected $empresaRepository;

    protected $useCaseUsuarios;

    public function __construct(UsuariosRepositoryInterface $usuariosRepository, EmpresaRepositoryInterface $empresaRepositoryInterface)
    {
        $this->usuariosRepository = $usuariosRepository;
        $this->empresaRepository = $empresaRepositoryInterface;
        $this->useCaseUsuarios = new UsuarioUseCase($usuariosRepository);
    }

    public function index()
    {
        $companies = $this->empresaRepository->find($this->tenantId());

        return view('content.pages.usuarios', [
            'companies' => $companies,
            'tipo_usuario' => Auth::user()->role->tipo_usuario,
        ]);
    }

    public function createUser(Request $request)
    {
        $rolesPermitidos = [
            UserRole::VENDEDOR,
            UserRole::ADMINISTRATIVO,
            UserRole::BACKOFFICE,
            UserRole::SUPERVISOR,
            UserRole::ADVOGADA,
            UserRole::FINANCEIRO,
        ];
        if ((int) Auth::user()->user_role_id === UserRole::DEVELOPER) {
            $rolesPermitidos[] = UserRole::DEVELOPER;
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'user_role_id' => ['required', 'integer', Rule::in($rolesPermitidos)],
            'password' => ['required', 'string', 'min:8'],
        ]);
        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            return response()->json([
                'error' => true,
                'message' => $firstError,
            ], 422);
        }

        $data = $validator->validated();
        $data['empresa_id'] = $this->tenantId();

        return $this->useCaseUsuarios->createUser($data);
    }

    public function getUsers()
    {
        $vendas = $this->usuariosRepository->usersAccordingToPermission(Auth::user()->role->id, $this->tenantId(), Auth::user()->id);

        return response()->json(['data' => $vendas]);
    }

    public function editUser($idUser)
    {
        $managedUser = $this->managedUser((int) $idUser);

        return view('content.pages.editarUsuario', [
            'user' => $managedUser,
        ]);
    }

    public function updateUser(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'ativo' => ['required', 'in:Y,N'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'birthdate' => ['nullable', 'date'],
        ]);
        $this->managedUser((int) $data['user_id']);
        $empresaId = $this->tenantId();

        return $this->useCaseUsuarios->updateUser($data, $empresaId);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'senha' => ['required', 'string', 'min:8'],
            'senhaConfirma' => ['required', 'same:senha'],
        ]);
        $this->managedUser((int) $data['user_id']);
        $empresaId = $this->tenantId();

        return $this->useCaseUsuarios->resetPassword($data, $empresaId);
    }

    public function save(Request $req, $userId)
    {
        $this->managedUser((int) $userId);
        $data = $req->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'banco' => ['nullable', 'string', 'max:255'],
            'agencia' => ['nullable', 'string', 'max:50'],
            'conta' => ['nullable', 'string', 'max:50'],
            'digito' => ['nullable', 'string', 'max:20'],
            'chave_pix' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $userId) {
            DB::table('contas_pagamento')
                ->where('user_id', $userId)
                ->update(['is_default' => 0]);

            DB::table('contas_pagamento')->updateOrInsert(
                [
                    'user_id' => $userId,
                ],
                [
                    'descricao' => $data['descricao'] ?? null,
                    'banco' => $data['banco'] ?? null,
                    'agencia' => $data['agencia'] ?? null,
                    'conta' => $data['conta'] ?? null,
                    'digito' => $data['digito'] ?? null,
                    'chave_pix' => $data['chave_pix'] ?? null,
                    'is_default' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });

        return response()->json(['message' => 'Conta cadastrada como padrão com sucesso.']);
    }

    public function toggleStatus($userId)
    {
        $managedUser = $this->managedUser((int) $userId);

        // Não permitir desativar o próprio usuário logado
        if ($managedUser->id == auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode desativar sua própria conta.',
            ], 422);
        }

        // Alterna o status
        $novoStatus = $managedUser->ativo === 'Y' ? 'N' : 'Y';
        $managedUser->ativo = $novoStatus;
        $managedUser->save();

        return response()->json([
            'success' => true,
            'message' => $novoStatus === 'Y' ? 'Usuário ativado com sucesso.' : 'Usuário desativado com sucesso.',
            'status' => $novoStatus,
        ]);
    }

    public function getStats()
    {
        $empresaId = $this->tenantId();

        $membros = DB::table('users')
            ->where('empresa_id', $empresaId)
            ->where('is_platform_admin', false);
        $total = (clone $membros)->count();
        $ativos = (clone $membros)->where('ativo', 'Y')->count();
        $inativos = (clone $membros)->where('ativo', 'N')->count();

        // Novos usuários este mês
        $inicioMes = now()->startOfMonth();
        $novosEsteMes = (clone $membros)
            ->where('created_at', '>=', $inicioMes)
            ->count();

        return response()->json([
            'total' => $total,
            'ativos' => $ativos,
            'inativos' => $inativos,
            'novos_mes' => $novosEsteMes,
        ]);
    }

    private function managedUser(int $userId): User
    {
        $managedUser = User::query()
            ->where('empresa_id', $this->tenantId())
            ->where('is_platform_admin', false)
            ->findOrFail($userId);

        abort_if(
            (int) Auth::user()->user_role_id !== UserRole::DEVELOPER
                && (int) $managedUser->user_role_id === UserRole::DEVELOPER,
            403
        );

        return $managedUser;
    }
}
