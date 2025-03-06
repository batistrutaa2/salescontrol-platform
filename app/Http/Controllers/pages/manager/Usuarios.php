<?php

namespace App\Http\Controllers\pages\manager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\UseCases\UsuarioUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Usuarios extends Controller
{
  protected $usuariosRepository;
  protected $empresaRepository;
  protected $useCaseUsuarios;

  private $rulesCreateUser = [
    'name' => 'required|string',
    'email' => 'required|string|email|max:255|unique:users',
    'user_role_id' => 'required|string',
    'empresa_id' => 'required|string',
    'password' => 'required|string'
  ];


  public function __construct(UsuariosRepositoryInterface $usuariosRepository, EmpresaRepositoryInterface $empresaRepositoryInterface)
  {
    $this->usuariosRepository = $usuariosRepository;
    $this->empresaRepository = $empresaRepositoryInterface;
    $this->useCaseUsuarios = new UsuarioUseCase($usuariosRepository);
  }


  public function index()
  {
    $companies = Auth::user()->role->id == UserRole::DEVELOPER ? $this->empresaRepository->all() : $this->empresaRepository->find(Auth::user()->empresa_id);
    return view('content.pages.usuarios', [
      'companies' => $companies,
      'tipo_usuario' => Auth::user()->role->tipo_usuario
    ]);
  }


  public function createUser(Request $request)
  {
    $validator = Validator::make($request->all(), $this->rulesCreateUser);
    if ($validator->fails()) {
      $firstError = $validator->errors()->first();

      return response()->json([
        'error' => true,
        'message' => $firstError
      ], 422);
    }

    return $this->useCaseUsuarios->createUser($request->all());
  }

  public function getUsers()
  {
    $vendas = $this->usuariosRepository->usersAccordingToPermission(Auth::user()->role->id, Auth::user()->empresa_id, Auth::user()->id);
    return response()->json(['data' => $vendas]);
  }


  public function editUser($idUser)
  {
    $user = $this->usuariosRepository->find($idUser);
    return view('content.pages.editarUsuario', [
      'user' => $user,
    ]);
  }


  public function updateUser(Request $request)
  {
    return $this->useCaseUsuarios->updateUser($request->all());
  }

}
