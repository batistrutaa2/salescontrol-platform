<?php

namespace App\Http\Controllers\pages\mailing;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BaseLegaceRespositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\BaseLegaceRespository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\VendasRepository;
use App\UseCases\MailingUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Mailing extends Controller
{
  protected UsuariosRepositoryInterface $usuarioRepository;
  protected TabulacoesRepository $tabulacoesRepository;

  protected BaseLegaceRespository $baseLegaceRespository;
  protected MailingUseCase $mailingUseCase;
  protected ContatosRepository $contatosRepository;
  protected VendasRepository $vendasRepository;
  private $rulesUpload = [
    'base' => 'required|string',
    'file' => 'required|file',
  ];

  public function __construct(
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface,
    TabulacoesRepositoryInterface $tabulacoesRepositoryInterface,
    BaseLegaceRespositoryInterface $baseLegaceRespositoryInterface,
    VendasRepositoryInterface $vendasRepositoryInterface
  ) {

    $this->mailingUseCase = new MailingUseCase($contatosRepositoryInterface);

    $this->contatosRepository = $contatosRepositoryInterface;
    $this->usuarioRepository = $usuariosRepositoryInterface;
    $this->tabulacoesRepository = $tabulacoesRepositoryInterface;
    $this->baseLegaceRespository = $baseLegaceRespositoryInterface;
    $this->vendasRepository = $vendasRepositoryInterface;
  }

  public function index()
  {
    $users = Auth::user()->role->id == UserRole::DEVELOPER ? $this->usuarioRepository->all() : $this->usuarioRepository->getUserByCompany(Auth::user()->empresa_id);
    $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);
    return view('content.pages.mailing.importMailing', [
      'users' => $users,
      'tabulacoes' => $tabulacoes
    ]);
  }

  public function importaMailing(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), $this->rulesUpload);
      if ($validator->fails()) {
        $firstError = $validator->errors()->first();

        return response()->json([
          'error' => true,
          'message' => $firstError
        ], 422);
      }

      $this->validateFileUploadExcel($request);

      return $this->mailingUseCase->importaMailing($request);
    } catch (\Throwable $th) {
      return response()->json([
        'error' => true,
        'message' => $th->getMessage()
      ]);
    }
  }


  public function deleteMailing($id)
  {
    $searchForLaunchedSale = $this->vendasRepository->checkExistenceSale($id);

    if (!$searchForLaunchedSale) {
      return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', "Esse Lead possui venda cadastrada, exclusão cancelada.");
    }

    // CRIA UM CAMPO DE STATUS PARA DESATIVAR O LEAD.

  }

  public function viewLeads()
  {
    $users = $this->usuarioRepository->getUserByCompany(Auth::user()->empresa_id);
    $tabulations = $this->tabulacoesRepository->getAll(Auth::user()->empresa_id);

    return view('content.pages.mailing.visualizar-leads', [
      'users' => $users,
      'tabulations' => $tabulations
    ]);
  }

  public function viewLeadslegacy()
  {
    $contacts = $this->baseLegaceRespository->getContactsAll();

    return view('content.pages.mailing.visualizar-leads-legado', [
      'contatos' => $contacts
    ]);
  }

  public function getLeads()
  {
    $data = $this->contatosRepository->getLeads(Auth::user()->empresa_id);
    return response()->json(['data' => $data]);
  }

  public function getLeadsLegacy($id_mailing)
  {
    $infoContact = $this->baseLegaceRespository->getContacts($id_mailing);
    $comments = $this->baseLegaceRespository->getCommentsMailing($id_mailing);

    return response()->json(
      [
        'contato' => $infoContact,
        'comentarios' => $comments
      ]
    );
  }

  public function contactsAdvertisement() {
    return view('content.pages.mailing.leads-anuncio');
  }
}
