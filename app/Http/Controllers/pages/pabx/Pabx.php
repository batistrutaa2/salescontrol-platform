<?php

namespace App\Http\Controllers\pages\pabx;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\RamaisRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Contracts\RamaisRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;

class Pabx extends Controller
{
  protected RamaisRepository $ramaisRepository;
  protected UsuariosRepository $usuariosRepository;

  public function __construct(
    RamaisRepositoryInterface $ramaisRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface
  ) {
    $this->ramaisRepository = $ramaisRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
  }

  public function createRamal()
  {
    $usuarios = $this->usuariosRepository->usersAccordingToPermission(Auth::user()->user_role_id, Auth::user()->empresa_id, Auth::user()->id);
    return view('content.pages.pabx.cadastroRamais', [
      'usuarios' => $usuarios
    ]);
  }


  public function getRamais()
  {
    $ramais = $this->ramaisRepository->getRamais(Auth::user()->user_role_id, Auth::user()->empresa_id);
    return response()->json([
      'data' => $ramais
    ]);
  }
}
