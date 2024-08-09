<?php

namespace App\Http\Controllers\pages\comerical;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ComentariosLegadosRepositoryInterface;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Eloquent\ComentariosLegadosRepository;
use App\Repositories\Eloquent\ComentariosRepository;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\UseCases\ComercialUseCase;
use Dotenv\Util\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class Comercial extends Controller
{
  protected ContatosCorretoresRepository  $repositoryContatosCorretoresRepository;
  protected TabulacoesRepository  $tabulacoesRepository;
  protected ContatosRepository  $contatosRepository;
  protected ComentariosRepository  $comentariosRepository;
  protected UsuariosRepository  $usuariosRepository;
  protected ComentariosLegadosRepository  $comentariosLegadosRepository;

  protected ComercialUseCase  $comercialUseCase;

  public function __construct(
    ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface,
    TabulacoesRepositoryInterface $TabulacoesRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface,
    ComentariosRepositoryInterface $comentariosRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ComentariosLegadosRepositoryInterface $comentariosLegadosRepositoryInterface
  ) {
    //Repositories
    $this->repositoryContatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
    $this->tabulacoesRepository = $TabulacoesRepositoryInterface;
    $this->contatosRepository = $contatosRepositoryInterface;
    $this->comentariosRepository = $comentariosRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
    $this->comentariosLegadosRepository = $comentariosLegadosRepositoryInterface;

    //UseCases
    $this->comercialUseCase = new ComercialUseCase($contatosRepositoryInterface, $contatosCorretoresRepositoryInterface, $comentariosRepositoryInterface);
  }

  public function index()
  {
    $vendedores = $this->usuariosRepository->getUsersFilterType(Auth::user()->empresa_id, UserRole::VENDEDOR);

    return view('content.pages.comercial.index', [
      'vendedores' => $vendedores,
      'typeUserLogeed' => Auth::user()->role->tipo_usuario
    ]);
  }

  public function getClientComercial()
  {
    $contacts = $this->repositoryContatosCorretoresRepository->getClientComercial(Auth::user()->user_role_id, Auth::user()->empresa_id);
    $structuredData = $this->structureBoardData($contacts);

    return response()->json($structuredData);
  }

  protected function structureBoardData($contacts)
  {
    $status = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);

    $boardData = [];


    foreach ($status as $tabulation) {

      $items = $contacts->filter(function ($contact) use ($tabulation) {
        return $contact->id == $tabulation['id'];
      })->map(function ($contact) use ($tabulation) {

        return [
          'id' =>  $contact->idContato,
          'title' => $contact->nome_cliente,
          'comments' => (string) $contact->qt_comentarios,
          'badge-text' => $contact->temperatura,
          'badge' => $this->getColorText($contact->temperatura),
          'attachments' => '',
          'nome_cliente' =>  $contact->nome_cliente,
          'data_nascimento' =>  $contact->data_nascimento,
          'cpf' =>  $contact->cpf,
          'plano' =>  $contact->plano,
          'categoria' =>  $contact->categoria,
          'entidade' =>  $contact->entidade,
          'telefone1' =>  $contact->telefone1,
          'telefone2' =>  $contact->telefone2,
          'telefone3' =>  $contact->telefone3,
          'email' =>  $contact->email,
          'temperatura' => $contact->temperatura,
          'valor' =>  $contact->valor_plano_atual,
          'valor_negociacao' =>  $contact->valor_negociacao,
          'user-id' => $contact->user_id,
          'time_expired' => $this->arriveExpirationTime($contact->updated_at, $tabulation['id'], $contact->created_at)

        ];
      })->values()->toArray();

      $boardData[] = [
        'id' =>  Helpers::normalizeStatusName($tabulation['id']),
        'title' => $tabulation['descricao'],
        'order' => $tabulation['ordem_kanban'],
        'item' => $items
      ];
    }

    usort($boardData, function ($a, $b) {
      return strcmp($a['order'], $b['order']);
    });

    return $boardData;
  }

  public function arriveExpirationTime(string $dateTimeUpdate, string $tabulationId, string $dateTimeCreated)
  {
    $dataCreatedLead = Carbon::createFromFormat('d/m/Y H:i:s', $dateTimeCreated)->startOfDay();
    $dataUpdateLead = Carbon::createFromFormat('d/m/Y H:i:s', $dateTimeUpdate)->startOfDay();
    $dataCurrent = Carbon::now()->startOfDay();

    $differenceInDaysCreated = (int) $dataCreatedLead->diffInDays($dataCurrent);
    $differenceInDaysUpdate = (int) $dataUpdateLead->diffInDays($dataCurrent);


    if ($tabulationId == Tabulations::PROSPECCAO) {
      return $differenceInDaysCreated > 5;
    } elseif ($tabulationId == Tabulations::NEGOCIAÇÃO) {
      return $differenceInDaysCreated > 10;
    } elseif ($tabulationId == Tabulations::DOCUMENTO) {
      return $differenceInDaysUpdate > 15;
    } else {
      return false;
    }
  }

  public function changeStatusLead(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'contato_id' => 'required|integer',
      'tabulacao_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => 'Invalid data'], 400);
    }

    $saveStatus = $this->repositoryContatosCorretoresRepository->changeStatusLead($request->all());

    if ($saveStatus) {
      return response()->json(
        [
          'error' => false,
          'message' => 'Status atualizado com sucesso.'
        ],
        200
      );
    } else {
      return response()->json(
        [
          'error' => true,
          'message' => 'Erro ao atualizar status.'
        ],
        501
      );
    }
  }


  public function saveNoteMailing(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'id_mailing' => 'required|integer'
    ]);

    if ($validator->fails()) {
      return response()->json(['error' => true, 'message' => 'Erro ao efetuar salvar informações. contate nosso suporte.'], 501);
    }

    return $this->comercialUseCase->saveDataInfo(
      $request->id_mailing,
      $request->telefone1,
      $request->telefone2,
      $request->telefone3,
      $request->comments,
      $request->temperatura,
      $request->valor_negociacao
    );
  }


  public function getCommentsLead($id_mailing)
  {
    $comments = $this->comentariosRepository->getCommentsMailing($id_mailing);
    return response()->json($comments);
  }


  public function openClient($id_mailing)
  {
    $clientInfo = $this->repositoryContatosCorretoresRepository->getClientInfo($id_mailing);
    $commentsMailing = $this->comentariosRepository->getCommentsMailingAll($id_mailing);
    $tabulations = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);
    $tabulationCurrent = $this->repositoryContatosCorretoresRepository->getTabulationId($id_mailing);

    $permiteEdition = false;
    if (Auth::user()->role->id === UserRole::ADMINISTRATIVO || Auth::user()->role->id === UserRole::DEVELOPER) {
      $permiteEdition = true;
    }

    return view('content.pages.comercial.openClient', [
      'client' => $clientInfo,
      'comments' => $commentsMailing,
      'editingPermission' => $permiteEdition,
      'tabulations' => $tabulations,
      'tabulationCurrent' => $tabulationCurrent->tabulacao_id
    ]);
  }

  public function updateClient(Request $request)
  {
    try {
      $updateClient =  $this->contatosRepository->updateOrCreate($request->all());
      $updatetemperature =  $this->repositoryContatosCorretoresRepository->updateTemperatureAndTabulation($request->temperatura, $request->id, $request->tabulacao_id);

      if ($updateClient && $updatetemperature) {
        return redirect()->back()->with('status', 'success')->with('message', 'Dados atualizados com sucesso.');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Falha ao atualizar status.');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Falha ao atualizar status.');
    }
  }


  public function saveComment(Request $request)
  {
    $saveComment = $this->comentariosRepository->createComment(
      Auth::user()->empresa_id,
      Auth::user()->id,
      $request->anotacao,
      $request->id_mailing
    );

    if ($saveComment) {
      return response()->json(
        [
          'error' => false,
          'message' => 'Comentario feito com sucesso.'
        ],
        200
      );
    } else {
      return response()->json(
        [
          'error' => true,
          'message' => 'Erro ao salvar comentario'
        ],
        501
      );
    }
  }


  public function remarketing()
  {
    return view('content.pages.comercial.remarketing');
  }


  public function getRemarketingLeads()
  {
    $contatos = $this->repositoryContatosCorretoresRepository->getRemarketingLeads(Auth::user()->empresa_id);
    return response()->json($contatos);
  }

  public function openLeadRemarketing(string $id_mailing)
  {
    $comments = $this->comentariosRepository->getCommentsMailingAll($id_mailing);
    $contact = $this->contatosRepository->find($id_mailing);
    $user = $this->usuariosRepository->usersAccordingToPermission(Auth::user()->role->id, Auth::user()->empresa_id, Auth::user()->id);

    return view('content.pages.comercial.openRemarketing', [
      'comments' => $comments,
      'client' => $contact,
      'users' => $user
    ]);
  }

  public function transferContact(Request $request)
  {
    dd($request->all());
  }


  public function getCommentsLegacy(string $cpf)
  {
    $commentsLegacy = $this->comentariosLegadosRepository->getCommentsLegacy($cpf);
    return response()->json($commentsLegacy);
  }
}
