<?php

namespace App\Http\Controllers\pages\comerical;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\AgendamentoRepositoryInterface;
use App\Repositories\Contracts\ComentariosLegadosRepositoryInterface;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\LeadAtividadeRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\AgendamentoRepository;
use App\Repositories\Eloquent\ComentariosLegadosRepository;
use App\Repositories\Eloquent\ComentariosRepository;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\LeadAtividadeRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Eloquent\VendasRepository;
use App\UseCases\ComercialUseCase;
use App\UseCases\MailingUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class Comercial extends Controller
{
  protected ContatosCorretoresRepository $repositoryContatosCorretores;
  protected TabulacoesRepository $tabulacoesRepository;
  protected ContatosRepository $contatosRepository;
  protected ComentariosRepository $comentariosRepository;
  protected UsuariosRepository $usuariosRepository;
  protected VendasRepository $vendasRepository;
  protected ComentariosLegadosRepository $comentariosLegadosRepository;
  protected LeadAtividadeRepository $leadAtividadeRepository;
  protected ComercialUseCase $comercialUseCase;
  protected MailingUseCase $mailingUseCase;

  protected AgendamentoRepository $agendamentoRepository;

  public function __construct(
    ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface,
    TabulacoesRepositoryInterface $TabulacoesRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface,
    ComentariosRepositoryInterface $comentariosRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ComentariosLegadosRepositoryInterface $comentariosLegadosRepositoryInterface,
    VendasRepositoryInterface $vendasRepositoryInterface,
    LeadAtividadeRepositoryInterface $leadAtividadeRepositoryInterface,
    AgendamentoRepositoryInterface $agendamentoRepositoryInterface

  ) {
    //Repositories
    $this->repositoryContatosCorretores = $contatosCorretoresRepositoryInterface;
    $this->tabulacoesRepository = $TabulacoesRepositoryInterface;
    $this->contatosRepository = $contatosRepositoryInterface;
    $this->comentariosRepository = $comentariosRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
    $this->comentariosLegadosRepository = $comentariosLegadosRepositoryInterface;
    $this->vendasRepository = $vendasRepositoryInterface;
    $this->leadAtividadeRepository = $leadAtividadeRepositoryInterface;
    $this->agendamentoRepository = $agendamentoRepositoryInterface;

    //UseCases
    $this->comercialUseCase = new ComercialUseCase($contatosRepositoryInterface, $contatosCorretoresRepositoryInterface, $comentariosRepositoryInterface, $leadAtividadeRepositoryInterface);
    $this->mailingUseCase = new MailingUseCase($contatosRepositoryInterface);
  }

  public function index()
  {
    $vendedores = $this->usuariosRepository->getUsersFilterType(Auth::user()->empresa_id, UserRole::VENDEDOR);

    $subTabulacoes = $this->tabulacoesRepository->getSubTabulations(Auth::user()->empresa_id);

    return view('content.pages.comercial.index', [
      'vendedores' => $vendedores,
      'typeUserLogeed' => Auth::user()->role->tipo_usuario,
      'subTabulacoes' => $subTabulacoes
    ]);
  }

  public function getClientComercial()
  {
    $contacts = $this->repositoryContatosCorretores->getClientComercial(Auth::user()->user_role_id, Auth::user()->empresa_id);
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

        $typeUser = $this->showNameUserCard($this->usuariosRepository->getTypeUser(Auth::user()->id));

        return [
          'id' => $contact->idContato,
          'title' => $contact->nome_cliente,
          'comments' => (string) $contact->qt_comentarios,
          'badge-text' => $contact->temperatura,
          'badge' => $this->getColorText($contact->temperatura),
          'attachments' => '',
          'nome_cliente' => $contact->nome_cliente,
          'data_nascimento' => $contact->data_nascimento,
          'cpf' => $contact->cpf,
          'plano' => $contact->plano,
          'categoria' => $contact->categoria,
          'entidade' => $contact->entidade,
          'telefone1' => $contact->telefone1,
          'telefone2' => $contact->telefone2,
          'telefone3' => $contact->telefone3,
          'tabulacao-id' => $tabulation['id'],
          'email' => $contact->email,
          'idades' => $contact->idades,
          'temperatura' => $contact->temperatura,
          'valor' => $contact->valor_plano_atual,
          'valor_negociacao' => $contact->valor_negociacao,
          'user-id' => $contact->user_id,
          'user-name' => $contact->nameVendedor,
          'show-name-card' => $typeUser,
          'data_create' => $contact->created_at
        ];
      })->values()->toArray();

      $boardData[] = [
        'id' => Helpers::normalizeStatusName($tabulation['id']),
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

  private function showNameUserCard($typeUser): bool
  {
    if ($typeUser->user_role_id == UserRole::ADMINISTRATIVO || $typeUser->user_role_id == UserRole::BACKOFFICE) {
      return true;
    }
    return false;
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

    $saveStatus = $this->repositoryContatosCorretores->changeStatusLead($request->all());

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



  public function updateClient(Request $request)
  {
    try {
      $updateClient = $this->contatosRepository->updateOrCreate($request->all());
      $updatetemperature = $this->repositoryContatosCorretores->updateTemperatureAndTabulation($request->temperatura, $request->id, $request->tabulacao_id);

      if ($updateClient && $updatetemperature) {
        return redirect()->back()->with('status', 'success')->with('message', 'Dados atualizados com sucesso.');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Falha ao atualizar status.');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Falha ao atualizar status.');
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
      $request,
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
    $clientInfo = $this->repositoryContatosCorretores->getClientInfo($id_mailing);
    $commentsMailing = $this->comentariosRepository->getCommentsMailingAll($id_mailing);
    $tabulations = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);
    $tabulationCurrent = $this->repositoryContatosCorretores->getTabulationId($id_mailing);
    $subTabulacoes = $this->tabulacoesRepository->getSubTabulations(Auth::user()->empresa_id);

    $permiteEdition = false;
    if (Auth::user()->role->id === UserRole::ADMINISTRATIVO || Auth::user()->role->id === UserRole::DEVELOPER) {
      $permiteEdition = true;
    }

    return view('content.pages.comercial.openClient', [
      'client' => $clientInfo,
      'comments' => $commentsMailing,
      'editingPermission' => $permiteEdition,
      'tabulations' => $tabulations,
      'tabulationCurrent' => $tabulationCurrent->tabulacao_id,
      'subTabulacoes' => $subTabulacoes
    ]);
  }


  public function saveComment(Request $request)
  {
    $this->leadAtividadeRepository->create($request->all());

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
    $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
    $tabulations = $this->tabulacoesRepository->getAll(Auth::user()->empresa_id);

    return view('content.pages.comercial.remarketing', [
      'users' => $users,
      'tabulations' => $tabulations
    ]);
  }


  public function getRemarketingLeads()
  {
    $contatos = $this->repositoryContatosCorretores->getRemarketingLeads(Auth::user()->empresa_id);
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
    try {
      $updateLead = $this->repositoryContatosCorretores->transferContact($request->all());
      if ($updateLead) {
        return redirect()->back()->with('status', 'success')->with('message', 'Transferencia concluida com sucesso');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead');
    }
  }

  public function transferContactInNulk(Request $request)
  {
    try {
      $updateLead = $this->repositoryContatosCorretores->transferContactInNulk($request->all());
      if ($updateLead) {
        return redirect()->back()->with('status', 'success')->with('message', 'Transferencia concluida com sucesso');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead');
      }
    } catch (\Throwable $th) {
      dd($th);
      return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead');
    }
  }


  public function getCommentsLegacy(string $cpf)
  {
    $commentsLegacy = $this->comentariosLegadosRepository->getCommentsLegacy(Helpers::cleanSpecialCharacters($cpf));
    return response()->json($commentsLegacy);
  }

  public function createSale(Request $request)
  {
    try {
      $saveSale = $this->vendasRepository->create($request->all());

      $arrayData = [
        'contato_id' => $request->contato_id,
        'tabulacao_id' => Tabulations::VENDA
      ];

      $updateStatusContact = $this->repositoryContatosCorretores->changeStatusLead($arrayData);

      if ($saveSale && $updateStatusContact) {
        return redirect()->route('sale.listSale')->with('status', 'success')->with('message', 'Venda Cadastrada com sucesso');
      } else {
        return redirect()->route('sale.listSale')->with('status', 'error')->with('message', 'Falha ao atualizar status.');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Falha ao Cadastrar Venda');
    }
  }


  public function createClient()
  {
    return view('content.pages.mailing.criar-lead');
  }


  public function createLead(Request $request)
  {
    $response = $this->mailingUseCase->createLead($request->all());

    if ($response['error']) {
      return redirect()
        ->back()
        ->withInput()
        ->with('status', $response['status'])
        ->with('message', $response['message']);
    } else {
      return redirect()->route('comercial.kanban')->with('status', 'success')->with('message', $response['message']);
    }
  }


  public function sendRemaketing(Request $request)
  {
    $this->agendamentoRepository->deleteSchedule($request->contato_id);
    $updateContact = $this->repositoryContatosCorretores->sendRemaketing($request->contato_id, $request->sub_tabulacao_id);

    if ($updateContact) {
      return redirect()->route(route: 'comercial.kanban')->with('status', 'success')->with('message', "Contato descartado com sucesso");
    } else {
      return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', "Erro ao descartado com sucesso");
    }
  }

  public function sendSchedule(Request $request)
  {
    try {
      $updateContact = $this->repositoryContatosCorretores->sendSchedule($request->contato_id);
      $sendSchecule = $this->agendamentoRepository->updateOrCreate($request->contato_id, $request->horario_agendamento, $request->observacao);

      if ($updateContact && $sendSchecule) {
        return redirect()->route(route: 'comercial.kanban')->with('status', 'success')->with('message', "Agendamento efetuado com sucesso");
      } else {
        return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', "Erro ao salvar agendamento");
      }
    } catch (\Throwable $th) {
      return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', "Erro ao Agendar contato");
    }
  }


  public function schedules()
  {
    $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);
    $subTabulacoes = $this->tabulacoesRepository->getSubTabulations(Auth::user()->empresa_id);

    $idNegocioFechado = Tabulations::NEGOCIO_FECHADO;
    $tabulacoes = $tabulacoes->reject(function ($item) use ($idNegocioFechado) {
      return $item->id === $idNegocioFechado;
    });

    return view('content.pages.comercial.agendamentos', [
      'subTabulacoes' => $subTabulacoes,
      'tabulacoes' => $tabulacoes
    ]);
  }

  public function getSchedules()
  {
    $schedules = $this->agendamentoRepository->getSchedules(Auth::user()->user_role_id);
    return response()->json([
      'data' => $schedules
    ]);
  }

  public function searchPendingAppointments()
  {
    $agendamentos = $this->agendamentoRepository->appointmentsDelaystonotify();
    return response()->json($agendamentos);
  }


  public function backQueue(Request $request)
  {
    try {
      $deleteSchedule = $this->agendamentoRepository->deleteSchedule($request->contato_id);
      $backQueueLead = $this->repositoryContatosCorretores->changeStatusLead($request->all());

      if ($deleteSchedule && $backQueueLead) {
        return redirect()->route(route: 'comercial.kanban')->with('status', 'success')->with('message', "Status atualizado");
      } else {
        return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', "Erro ao enviar para fila, contate nosso suporte.");
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', "Erro ao enviar para fila");
    }
  }
}
