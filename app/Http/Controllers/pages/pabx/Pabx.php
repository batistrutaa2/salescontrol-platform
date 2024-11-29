<?php

namespace App\Http\Controllers\pages\pabx;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\RamaisRepository;
use App\Repositories\Eloquent\EmpresaRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Contracts\RamaisRepositoryInterface;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;

class Pabx extends Controller
{
  protected RamaisRepository $ramaisRepository;
  protected UsuariosRepository $usuariosRepository;
  protected EmpresaRepository $empresaRepository;

  public function __construct(
    RamaisRepositoryInterface $ramaisRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    EmpresaRepositoryInterface $empresaRepositoryInterface
  ) {
    $this->ramaisRepository = $ramaisRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
    $this->empresaRepository = $empresaRepositoryInterface;
  }

  public function index()
  {
    $usuarios = $this->usuariosRepository->usersAccordingToPermission(Auth::user()->user_role_id, Auth::user()->empresa_id, Auth::user()->id);
    $companies = Auth::user()->role->id == UserRole::DEVELOPER ? $this->empresaRepository->all() : $this->empresaRepository->find(Auth::user()->empresa_id);

    return view('content.pages.pabx.cadastroRamais', [
      'usuarios' => $usuarios,
      'tipo_usuario' => Auth::user()->user_role_id,
      'companies' => $companies
    ]);
  }

  public function getRamais()
  {
    $ramais = $this->ramaisRepository->getRamais(Auth::user()->user_role_id, Auth::user()->empresa_id);
    return response()->json([
      'data' => $ramais
    ]);
  }
  public function createramal(Request $request)
  {
    try {
      $createOrUpdateRamal = $this->ramaisRepository->create($request->all());
      if (!is_null($createOrUpdateRamal)) {
        return redirect()->back()->with('status', 'success')->with('message', 'Ramal Cadastrado com sucesso');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Erro ao Cadastrar Ramal');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Erro ao Cadastrar Ramal');
    }
  }
}
