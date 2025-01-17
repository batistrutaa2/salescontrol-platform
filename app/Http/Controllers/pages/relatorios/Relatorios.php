<?php

namespace App\Http\Controllers\pages\relatorios;

use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
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

  protected ContatosCorretoresRepository $contatosCorretoresRepository;
  public function __construct(
    LigacoesRepositoryInterface $ligacoesRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ContatosCorretoresRepositoryInterface $ContatosCorretoresRepositoryInterface
  ) {
    $this->ligacoesRepository = $ligacoesRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
    $this->contatosCorretoresRepository = $ContatosCorretoresRepositoryInterface;
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
    $ligacoes = $this->ligacoesRepository->getLigacoes($id_user, $data_inicial, $data_final);
    $filaAtual = $this->contatosCorretoresRepository->getQueueCurrent($id_user);

    return response()->json([
      'ligacoes' => $ligacoes,
      'fila' => $filaAtual
    ]);
  }
}
