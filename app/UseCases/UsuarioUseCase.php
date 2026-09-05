<?php

namespace App\UseCases;

use App\Repositories\Contracts\UsuariosRepositoryInterface;

class UsuarioUseCase
{
    protected $usuarioRepository;

    public function __construct(UsuariosRepositoryInterface $usuarioRepository)
    {
        $this->usuarioRepository = $usuarioRepository;
    }

    public function createUser(array $data)
    {
        try {
            $createUser = $this->usuarioRepository->create($data);
            if ($createUser) {
                return response()->json(
                    [
                        'message' => 'Usuario criado com sucesso',
                        'error' => false,
                    ],
                    201
                );
            } else {
                return response()->json(
                    [
                        'message' => 'Erro ao criar usuario',
                        'error' => true,
                    ],
                    500
                );
            }
        } catch (\Throwable $th) {
            report($th);

            return response()->json(['success' => false, 'error' => 'Não foi possível criar o usuário.'], 500);
        }
    }

    public function updateUser(array $data, int $empresaId)
    {
        try {
            $updated = $this->usuarioRepository->editUser($data, $empresaId);
            if ($updated) {
                return redirect()->route('usuarios.index')->with('status', 'success')->with('message', 'Usuario editado com sucesso');
            }

            return redirect()->back()->with('status', 'error')->with('message', 'Não foi possível atualizar o usuário');
        } catch (\Throwable $th) {
            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao atualizar usuario, contate nosso suporte');
        }
    }

    public function resetPassword(array $data, int $empresaId)
    {
        try {
            if (empty($data['user_id']) || empty($data['senha']) || empty($data['senhaConfirma'])) {
                return redirect()->back()->with('status', 'error')->with('message', 'Preencha todos os campos de senha');
            }

            if ($data['senha'] !== $data['senhaConfirma']) {
                return redirect()->back()->with('status', 'error')->with('message', 'As senhas não conferem');
            }

            $updated = $this->usuarioRepository->updatePassword((int) $data['user_id'], $empresaId, $data['senha']);
            if ($updated) {
                return redirect()->back()->with('status', 'success')->with('message', 'Senha atualizada com sucesso');
            }

            return redirect()->back()->with('status', 'error')->with('message', 'Não foi possível atualizar a senha');
        } catch (\Throwable $th) {
            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao atualizar senha, contate nosso suporte');
        }
    }
}
