<?php

namespace App\Http\Controllers\pages\pabx;

use App\Models\ContatosCorretores;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use Helper;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\pabx\voipmais\SdkVoipMais;
use App\Repositories\Eloquent\RamaisRepository;
use App\Repositories\Eloquent\EmpresaRepository;
use App\Repositories\Eloquent\LigacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Contracts\RamaisRepositoryInterface;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;

class Pabx extends Controller
{
  protected RamaisRepository $ramaisRepository;
  protected LigacoesRepository $ligacoesRepository;
  protected ContatosCorretoresRepository $contatosCorretores;
  protected UsuariosRepository $usuariosRepository;
  protected EmpresaRepository $empresaRepository;
  protected SdkVoipMais $sdkVoipMais;

  public function __construct(
    RamaisRepositoryInterface $ramaisRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    EmpresaRepositoryInterface $empresaRepositoryInterface,
    LigacoesRepositoryInterface $ligacoesRepositoryInterface,
    ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface
  ) {
    $this->ramaisRepository = $ramaisRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
    $this->empresaRepository = $empresaRepositoryInterface;
    $this->sdkVoipMais = new SdkVoipMais();
    $this->ligacoesRepository = $ligacoesRepositoryInterface;
    $this->contatosCorretores = $contatosCorretoresRepositoryInterface;
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

  public function clickToCall(Request $request)
  {
    try {
      $paramentsRamal = $this->ramaisRepository->getRamal(Auth::user()->id);

      if (is_null($paramentsRamal)) {
        return response()->json([
          'error' => true,
          'message' => "parametros de ramal não foram cadastrados"
        ], 404);
      }
      $response = $this->sdkVoipMais->makeClickToCall($paramentsRamal->ramal, Helpers::cleanSpecialCharacters($request->telefone));
      $tabulacao_id = $this->contatosCorretores->getTabulationId($request->contato_id);
      $arraySave = [
        'empresa_id' => Auth::user()->empresa_id,
        'user_id' => Auth::user()->id,
        'contato_id' => $request->contato_id,
        'telefone' => Helpers::cleanSpecialCharacters($request->telefone),
        'status' => $tabulacao_id->tabulacao_id,
        'id_call' => $response['idCall'] ?? ""
      ];
      $this->ligacoesRepository->create($arraySave);
      return response()->json([
        'error' => false,
        'message' => $response['data']
      ], 201);
    } catch (\Throwable $th) {
      return response()->json([
        'error' => true,
        'message' => "erro ao efetuar chamada"
      ], 404);
    }
  }
}
