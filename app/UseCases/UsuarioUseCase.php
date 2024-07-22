<?php

namespace App\UseCases;

use App\Repositories\Contracts\UsuariosRepositoryInterface;
use Illuminate\Http\Request;


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
            'message' => "Usuario criado com sucesso",
            'error' => false
          ],
          201
        );
      } else {
        return response()->json(
          [
            'message' => "Erro ao criar usuario",
            'error' => true
          ],
          500
        );
      }
    } catch (\Throwable $th) {
      return response()->json(['success' => false, 'error' => $th->getMessage()], 500);
    }
  }
}
