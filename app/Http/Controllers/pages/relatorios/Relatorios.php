<?php

namespace App\Http\Controllers\pages\relatorios;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\LigacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;

class Relatorios extends Controller
{
  protected LigacoesRepository $ligacoesRepository;
  protected UsuariosRepository $usuariosRepository;
  public function __construct(
    LigacoesRepositoryInterface $ligacoesRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface
  ) {
    $this->ligacoesRepository = $ligacoesRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
  }

  public function index()
  {
    $user = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
    return view('content.pages.relatorios.ligacoes', [
      'users' => $user
    ]);
  }


  public function getLigacoes($id_user, $data_inicial, $data_final)
  {
    $this->ligacoesRepository->getLigacoes($id_user, $data_inicial, $data_final);
  }
}
