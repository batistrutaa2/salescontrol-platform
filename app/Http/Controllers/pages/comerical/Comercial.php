<?php

namespace App\Http\Controllers\pages\comerical;

use App\Models\Comentarios;
use App\Models\Contatos;
use App\Models\Demanda;
use App\Models\Dependentes;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\User;
use DateTime;
use Carbon\Carbon;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Preditiva;
use App\Enums\Tabulations;
use App\Models\LogPreditiva;
use Illuminate\Http\Request;
use App\Modules\Ranking\Ranking;
use App\UseCases\MailingUseCase;
use App\Models\ContatosCorretores;
use App\UseCases\ComercialUseCase;
use App\UseCases\PreditivaUseCase;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\AgendamentoRepository;
use App\Repositories\Eloquent\ComentariosRepository;
use App\Repositories\Eloquent\LeadAtividadeRepository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Eloquent\ComentariosLegadosRepository;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Contracts\PreditivaRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Eloquent\TransferenciaContatoRepository;
use App\Repositories\Contracts\AgendamentoRepositoryInterface;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\LogPreditivaRepositoryInterface;
use App\Repositories\Contracts\LeadAtividadeRepositoryInterface;
use App\Repositories\Contracts\ComentariosLegadosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\TransferenciaContatoRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
  protected PreditivaUseCase $preditivaUseCase;
  protected AgendamentoRepository $agendamentoRepository;
  protected Ranking $ranking;
  protected TransferenciaContatoRepository $transferenciaContatoRepository;

  public function __construct(
    ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface,
    TabulacoesRepositoryInterface $TabulacoesRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface,
    ComentariosRepositoryInterface $comentariosRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ComentariosLegadosRepositoryInterface $comentariosLegadosRepositoryInterface,
    VendasRepositoryInterface $vendasRepositoryInterface,
    LeadAtividadeRepositoryInterface $leadAtividadeRepositoryInterface,
    AgendamentoRepositoryInterface $agendamentoRepositoryInterface,
    TransferenciaContatoRepositoryInterface $TransferenciaContatoRepositoryInterface,
    PreditivaRepositoryInterface $preditivaRepositoryInterface,
    LogPreditivaRepositoryInterface $logPreditivaRepositoryInterface,

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
    $this->transferenciaContatoRepository = $TransferenciaContatoRepositoryInterface;

    //UseCases
    $this->comercialUseCase = new ComercialUseCase($contatosRepositoryInterface, $contatosCorretoresRepositoryInterface, $comentariosRepositoryInterface, $leadAtividadeRepositoryInterface);
    $this->mailingUseCase = new MailingUseCase($contatosRepositoryInterface);
    $this->preditivaUseCase = new PreditivaUseCase($preditivaRepositoryInterface, $logPreditivaRepositoryInterface, $contatosCorretoresRepositoryInterface);
    //raking de vendas
    $this->ranking = new Ranking();
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
          'tipo-lead' => $contact->is_ads === "Y" ? "R" : "A",
          'data_create' => $contact->created_at,
          'data_update' => $contact->updated_at
        ];
      })->values()->toArray();

      usort($items, function ($a, $b) {
        $dataA = DateTime::createFromFormat('d/m/Y H:i:s', $a['data_create']);
        $dataB = DateTime::createFromFormat('d/m/Y H:i:s', $b['data_create']);

        if ($dataA && $dataB) {
          return $dataA->format('Y-m-d') <=> $dataB->format('Y-m-d');
        }

        return 0;
      });

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
    if ($typeUser->user_role_id == UserRole::ADMINISTRATIVO || $typeUser->user_role_id == UserRole::BACKOFFICE || $typeUser->user_role_id == UserRole::SUPERVISOR) {
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

  public function updateClientDependecies(Request $request)
  {
    try {
      $dependencies = Dependentes::find($request->id_dependente);

      $dependencies->nome = $request->dependentes[$request->index_array]["nome"];
      $dependencies->cpf = $request->dependentes[$request->index_array]["cpf"];
      $dependencies->idade = $request->dependentes[$request->index_array]["idade"];
      $dependencies->telefone_1 = $request->dependentes[$request->index_array]["telefone1"];
      $dependencies->telefone_2 = $request->dependentes[$request->index_array]["telefone2"];
      $dependencies->telefone_3 = $request->dependentes[$request->index_array]["telefone3"];
      $dependencies->valor_plano = Helpers::moneyForRealSaveBank($request->dependentes[$request->index_array]["valor_plano"]);
      if ($dependencies->save()) {
        return redirect()->back()->with('status', 'success')->with('message', 'Dependente: ' . $request->dependentes[$request->index_array]["nome"] . ' Atualizado com sucesso');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Falha ao atualizar dependente.');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Falha ao atualizar dependente.');
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
    $dependentes = "";
    $clientInfo = $this->repositoryContatosCorretores->getClientInfo($id_mailing);
    $totalFamilyPlan = $clientInfo->valor_plano_atual ?? 0;
    if ($clientInfo->tipo_layout != "padrao") {
      $dependentes = Dependentes::where('contato_id', $clientInfo->id)->get();
      foreach ($dependentes as $dependente) {
        $totalFamilyPlan += $dependente->valor_plano ?? 0;
      }
    }



    $commentsMailing = $this->comentariosRepository->getCommentsMailingAll($id_mailing);
    $tabulations = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);
    $tabulationCurrent = $this->repositoryContatosCorretores->getTabulationId($id_mailing);
    $subTabulacoes = $this->tabulacoesRepository->getSubTabulations(Auth::user()->empresa_id);

    $permiteEdition = false;
    if (Auth::user()->role->id === UserRole::ADMINISTRATIVO || Auth::user()->role->id === UserRole::DEVELOPER || Auth::user()->role->id === UserRole::SUPERVISOR) {
      $permiteEdition = true;
    }
    $cotacoes = [];
    $cotacoesPath = 'cotacoes/' . Auth::user()->empresa_id . '/' . $id_mailing;
    if (Storage::disk('public')->exists($cotacoesPath)) {
      foreach (Storage::disk('public')->files($cotacoesPath) as $file) {
        $cotacoes[] = [
          'name' => basename($file),
          'url' => Storage::url($file)
        ];
      }
    }


    $operadoras = Operadora::where('status', 'Y')
      ->orderBy('nome')
      ->get(['id', 'nome']);

    if (Auth::user()->empresa_id != $clientInfo->empresa_id) {
      return redirect()->route('comercial.kanban')->with('status', 'error')->with('message', 'Sem permissao de acesso');
    } else {
      return view('content.pages.comercial.openClient', [
        'client' => $clientInfo,
        'dependentes' => $dependentes,
        'totalFamilyPlan' => $totalFamilyPlan,
        'comments' => $commentsMailing,
        'editingPermission' => $permiteEdition,
        'tabulations' => $tabulations,
        'tabulationCurrent' => $tabulationCurrent->tabulacao_id,
        'subTabulacoes' => $subTabulacoes,
        'cotacoes' => $cotacoes,
        'operadoras' => $operadoras
      ]);
    }
  }


  public function uploadCotacao(Request $request, $id_mailing)
  {
    $request->validate([
      'file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048'
    ]);

    $empresaId = Auth::user()->empresa_id;
    $path = "cotacoes/{$empresaId}/{$id_mailing}";

    if ($request->filled('replace')) {
      Storage::disk('public')->delete($path . '/' . $request->input('replace'));
    }

    $file = $request->file('file');
    $filename = time() . '_' . $file->getClientOriginalName();
    $file->storeAs($path, $filename, 'public');

    return response()->json([
      'name' => $filename,
      'url' => Storage::url($path . '/' . $filename)
    ]);
  }

  public function deleteCotacao($id_mailing, $filename)
  {
    $empresaId = Auth::user()->empresa_id;
    $path = "cotacoes/{$empresaId}/{$id_mailing}/{$filename}";

    if (Storage::disk('public')->exists($path)) {
      Storage::disk('public')->delete($path);
    }

    return response()->json(['deleted' => true]);
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

    ContatosCorretores::where('contato_id', $request->id_mailing)->update([
      'updated_at' => now()
    ]);

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
      $existePreditiva = DB::table('preditiva') // ajuste o nome da tabela
        ->where('contato_id', $request->idMailing) // ajuste o campo
        ->exists();

      if ($existePreditiva) {
        DB::table('preditiva')
          ->where('contato_id', $request->idMailing)
          ->delete();
      }

      $fromUser = $this->repositoryContatosCorretores->getContactOwner($request->idMailing);
      $clearComments = $this->comentariosRepository->clearCommentsOne($request->idMailing);
      $updateLead = $this->repositoryContatosCorretores->transferContact($request->all());
      $saveTranfer = $this->transferenciaContatoRepository->saveTransfer(
        Auth::user()->empresa_id,
        $request->idMailing,
        $fromUser ? $fromUser->user_id : null,
        $request->user_id,
        Auth::user()->id
      );

      if ($updateLead && $clearComments && $saveTranfer) {
        return redirect()->back()->with('status', 'success')->with('message', 'Transferencia concluida com sucesso');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead.');
    }
  }

  public function transferContactInNulk(Request $request)
  {
    try {
      $leadIds = explode(',', $request->selectedLeadIds);
      array_map(function ($leadId) use ($request) {
        $fromUser = $this->repositoryContatosCorretores->getContactOwner($leadId);
        $this->transferenciaContatoRepository->saveTransfer(
          Auth::user()->empresa_id,
          $leadId,
          $fromUser->user_id == null ? $request->user_id : $fromUser->user_id,
          $request->user_id,
          Auth::user()->id
        );
      }, $leadIds);
      $clearComments = $this->comentariosRepository->clearComments($request->all());
      $updateLead = $this->repositoryContatosCorretores->transferContactInNulk($request->all());

      if ($updateLead && $clearComments) {
        return redirect()->back()->with('status', 'success')->with('message', 'Transferência concluída com sucesso');
      } else {
        return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferência de lead');
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferência de lead');
    }
  }



  public function getCommentsLegacy(string $cpf)
  {
    if (Auth::user()->empresa_id === 2) {
      $commentsLegacy = $this->comentariosLegadosRepository->getCommentsLegacy(Helpers::cleanSpecialCharacters($cpf));
      return response()->json($commentsLegacy);
    } else {
      $commentsLegacy = $this->comentariosLegadosRepository->getCommentsLegacy(Helpers::cleanSpecialCharacters(""));
      return response()->json($commentsLegacy);
    }
  }

  public function createSale(Request $request)
  {
    try {
      $saveSale = $this->vendasRepository->create($request->all());

      $arrayData = [
        'contato_id' => $request->contato_id,
        'tabulacao_id' => Tabulations::VENDA
      ];
      $updateStatus = $this->repositoryContatosCorretores->changeStatusLead($arrayData);

      if ($saveSale && $updateStatus) {
        return redirect()->route('sale.listSale')
          ->with('status', 'success')
          ->with('message', 'Venda Cadastrada com sucesso');
      }
      return redirect()->route('sale.listSale')
        ->with('status', 'error')
        ->with('message', 'Falha ao atualizar status.');
    } catch (\Throwable $th) {
      return redirect()->back()
        ->with('status', 'error')
        ->with('message', 'Falha ao Cadastrar Venda');
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
    if (!request()->ajax()) {
      if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
        return redirect()->intended('comercial/kanban');
      } else {
        return redirect()->intended('dashboard');
      }
    }

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

  public function indexMarketing()
  {
    $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
    $tabulations = $this->tabulacoesRepository->getAll(Auth::user()->empresa_id);
    return view("content.pages.comercial.filaMarketing", [
      'users' => $users,
      'tabulations' => $tabulations
    ]);
  }

  public function getLeadsmarketing()
  {
    $leads = $this->contatosRepository->getLeadsmarketing(Auth::user()->empresa_id);
    return response()->json($leads);
  }

  public function sendLeadMarketing(Request $request)
  {
    try {
      $saveLeadBroker = ContatosCorretores::create([
        'empresa_id' => Auth::user()->empresa_id,
        'contato_id' => $request->idMailing,
        'user_id' => $request->user_id,
        'tabulacao_id' => $request->tabulation_id,
        'temperatura' => "QUENTE",
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      if ($saveLeadBroker) {
        return redirect()->back()->with('status', 'success')->with('message', "Lead Enviado com sucesso");
      } else {
        return redirect()->back()->with('status', 'error')->with('message', "Erro ao enviar lead");
      }

    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', "Erro ao enviar lead");
    }
  }


  public function sendLeadPredictive(Request $request)
  {
    try {
      $respose = $this->preditivaUseCase->sendMailingPredictive($request->id);
      if ($respose) {
        return redirect()->back()->with('status', 'success')->with('message', "Lead Enviado com sucesso");
      } else {
        return redirect()->back()->with('status', 'error')->with('message', "Erro ao enviar lead para preditiva");
      }
    } catch (\Throwable $th) {
      return redirect()->back()->with('status', 'error')->with('message', "Erro ao enviar lead para preditiva");
    }
  }


  public function sendMultipleLeadsPredictive(Request $request)
  {
    try {
      $ids = $request->input('ids', []);

      if (empty($ids)) {
        return response()->json([
          'success' => false,
          'message' => 'Nenhum lead selecionado'
        ]);
      }

      $successCount = 0;
      $errorCount = 0;

      foreach ($ids as $id) {
        try {
          $result = $this->preditivaUseCase->sendMailingPredictive($id);
          if ($result) {
            $successCount++;
          } else {
            $errorCount++;
          }
        } catch (\Throwable $th) {
          $errorCount++;
        }
      }

      if ($errorCount === 0) {
        return response()->json([
          'success' => true,
          'message' => "$successCount lead(s) enviado(s) com sucesso para a fila preditiva."
        ]);
      } else if ($successCount > 0) {
        return response()->json([
          'success' => true,
          'message' => "$successCount lead(s) enviado(s) com sucesso e $errorCount com falha."
        ]);
      } else {
        return response()->json([
          'success' => false,
          'message' => "Falha ao enviar todos os $errorCount lead(s) para a fila preditiva."
        ]);
      }
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => "Erro ao processar: " . $th->getMessage()
      ], 500);
    }
  }


  public function getClientesPreditiva(Request $request)
  {
    $userId = Auth::id();
    $empresaId = Auth::user()->empresa_id;

    Preditiva::where('user_id', $userId)
      ->where('data_atribuicao', '<', now()->subHours(2))
      ->update([
        'user_id' => null,
        'data_atribuicao' => null
      ]);

    $cliente = DB::table('preditiva as p')
      ->join('contatos as c', 'p.contato_id', '=', 'c.id')
      ->leftJoin(DB::raw('(
                SELECT contato_id, COUNT(*) as descartes, MAX(created_at) as ultimo_descarte
                FROM log_preditiva
                WHERE acao = "DESCARTE"
                GROUP BY contato_id
            ) as lp'), 'p.contato_id', '=', 'lp.contato_id')
      ->select(
        'c.id',
        'c.nome_cliente as nome',
        'c.telefone1 as telefone',
        'c.email',
        'c.plano',
        'c.categoria',
        'c.entidade',
        'c.cpf',
        'c.data_nascimento',
        'c.valor_plano_atual',
        'c.created_at'
      )
      ->where('p.empresa_id', $empresaId)
      ->where('p.status', 'Y')
      ->where(function ($query) {
        $query->whereNull('p.user_id')
          ->orWhere('p.user_id', Auth::id());
      })
      ->where(function ($query) {
        $query->whereNull('lp.descartes')
          ->orWhere('lp.descartes', '<', 5);
      })
      ->orderBy('lp.ultimo_descarte', 'asc')
      ->orderBy('p.created_at', 'asc')
      ->limit(1)
      ->first();

    if ($cliente) {
      // Atribuir o cliente ao usuário atual
      Preditiva::where('contato_id', $cliente->id)
        ->update([
          'user_id' => $userId,
          'data_atribuicao' => now()
        ]);

      return response()->json([$cliente]);
    }
    return response()->json([]);
  }

  public function descartarClientePreditiva(Request $request)
  {
    $userId = Auth::id();
    $empresaId = Auth::user()->empresa_id;
    $contatoId = $request->contato_id;
    $tabulacao = $request->tabulacao ?? 'SEM TABULAÇÃO';

    // Registrar o descarte
    LogPreditiva::create([
      'empresa_id' => $empresaId,
      'user_id' => $userId,
      'contato_id' => $contatoId,
      'tabulacao' => $tabulacao,
      'acao' => 'DESCARTE'
    ]);

    // Liberar o cliente
    Preditiva::where('contato_id', $contatoId)
      ->update([
        'user_id' => null,
        'data_atribuicao' => null
      ]);

    return response()->json(['success' => true]);
  }

  public function converterClientePreditiva(Request $request)
  {
    $userId = Auth::id();
    $empresaId = Auth::user()->empresa_id;
    $contatoId = $request->contato_id;

    // Registrar a conversão no log
    LogPreditiva::create([
      'empresa_id' => $empresaId,
      'user_id' => $userId,
      'contato_id' => $contatoId,
      'tabulacao' => 'CONVERTIDO',
      'acao' => 'CONVERSAO'
    ]);

    // Buscar a tabulação "PROSPECÇÃO" da empresa
    $tabulacaoProspeccao = DB::table('tabulacoes')
      ->where('empresa_id', $empresaId)
      ->where('descricao', 'PROSPECÇÃO')
      ->where('status', 'Y')
      ->first();

    if ($tabulacaoProspeccao) {
      // Verificar se já existe um registro para este contato
      $existingRecord = DB::table('contatos_corretores')
        ->where('contato_id', $contatoId)
        ->where('user_id', $userId)
        ->first();

      if (!$existingRecord) {
        // Criar registro na tabela contatos_corretores
        DB::table('contatos_corretores')->insert([
          'empresa_id' => $empresaId,
          'contato_id' => $contatoId,
          'user_id' => $userId,
          'tabulacao_id' => $tabulacaoProspeccao->id,
          'sub_tabulacao_id' => null,
          'temperatura' => 'FRIO',
          'created_at' => now(),
          'updated_at' => now()
        ]);
      }
    }

    // Remover da fila preditiva
    Preditiva::where('contato_id', $contatoId)->delete();

    // Todos os comentarios anteriores que o vendedor fez será ocutado
    Comentarios::where('user_id', $userId)
      ->where('contato_id', $contatoId)
      ->where('empresa_id', $empresaId)
      ->update([
        'visivel' => "N"
      ]);

    return response()->json(['success' => true]);
  }


  public function descartarCliente($id_mailing)
  {
    try {
      DB::beginTransaction();

      $deleteLinked = $this->repositoryContatosCorretores->deleteMailing($id_mailing);

      if (!$deleteLinked) {
        throw new \Exception('Erro ao excluir mailing.');
      }

      $updateCustomer = Contatos::where('id', $id_mailing)->update([
        'status' => 'N',
        'updated_at' => now()
      ]);

      if (!$updateCustomer) {
        throw new \Exception('Erro ao atualizar contato.');
      }

      DB::commit();

      return response()->json(['success' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['success' => false, 'message' => 'Erro ao descartar cliente.'], 500);
    }
  }

  public function discardMultipleLeads(Request $request)
  {
    $ids = $request->input('ids');
    $clearPreditiva = $request->input('clearPreditiva');
    try {
      DB::transaction(function () use ($ids, $clearPreditiva) {
        foreach ($ids as $id) {
          if ($clearPreditiva) {
            $existePreditiva = DB::table('preditiva')
              ->where('contato_id', $id)
              ->exists();

            if ($existePreditiva) {
              DB::table('preditiva')->where('contato_id', $id)->delete();
              DB::table('log_preditiva')->where('contato_id', $id)->delete();
            }
          }

          $this->repositoryContatosCorretores->deleteMailing($id);

          Contatos::where('id', $id)->update([
            'status' => 'N',
            'updated_at' => now()
          ]);
        }
      });

      return response()->json(['success' => true, 'message' => 'Leads descartados com sucesso!']);
    } catch (\Exception $e) {
      return response()->json(['error' => false, 'message' => 'Erro ao descartar os leads.'], 500);
    }
  }

  public function getPlansByOperator($operadora_id)
  {
    $planos = Plano::where('operadora_id', $operadora_id)
      ->where('status', 'Y')
      ->orderBy('created_at', 'desc')
      ->get(['id', 'nome', 'acomodacao']);

    return response()->json($planos);
  }

  public function demands()
  {
    $users = User::select('id', 'name')
      ->wherein('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::BACKOFFICE])
      ->where('empresa_id', Auth::user()->empresa_id)
      ->where('ativo', 'Y')
      ->orderBy('name')->get();

    return view('content.pages.comercial.demandas', [
      'users' => $users,
    ]);
  }

  public function deletarDependente(Request $request)
  {
    $dependente = Dependentes::find($request->id_dependente);
    if ($dependente) {
      $dependente->delete();
      return redirect()->back()->with('status', 'success')->with('message', 'Dependente excluído com sucesso.');
    } else {
      return redirect()->back()->with('status', 'error')->with('message', 'Dependente não encontrado.');
    }
  }

  public function list(Request $request)
  {
    $q = Demanda::with(['criador:id,name', 'responsavel:id,name'])
      ->when($request->status && $request->status !== 'TODOS', fn($s) => $s->where('status', $request->status))
      ->when($request->prioridade && $request->prioridade !== 'TODAS', fn($s) => $s->where('prioridade', $request->prioridade))
      ->when($request->assigned_to, fn($s) => $s->where('assigned_to', $request->assigned_to))
      ->orderByDesc('created_at');

    return response()->json([
      'data' => $q->get()->map(function ($d) {
        return [
          'id' => $d->id,
          'titulo' => $d->titulo,
          'descricao' => Str::limit($d->descricao, 120),
          'prioridade' => $d->prioridade,
          'status' => $d->status,
          'responsavel' => $d->responsavel?->name,
          'criador' => $d->criador?->name,
          'data_limite' => optional($d->data_limite)->format('Y-m-d'),
          'created_at' => $d->created_at->toDateTimeString(),
        ];
      })
    ]);
  }

  public function store(Request $r)
  {
    $data = $r->validate([
      'titulo' => 'required|string|max:255',
      'descricao' => 'nullable|string',
      'prioridade' => 'required|in:BAIXA,MEDIA,ALTA',
      'assigned_to' => 'nullable|exists:users,id',
      'data_limite' => 'nullable|date',
    ]);

    $data['empresa_id'] = auth()->user()->empresa_id;
    $data['created_by'] = auth()->id();

    $d = Demanda::create($data);
    return response()->json(['ok' => true, 'id' => $d->id]);
  }

  public function update($id, Request $r)
  {
    $d = Demanda::findOrFail($id);
    $data = $r->validate([
      'titulo' => 'required|string|max:255',
      'descricao' => 'nullable|string',
      'prioridade' => 'required|in:BAIXA,MEDIA,ALTA',
      'assigned_to' => 'nullable|exists:users,id',
      'data_limite' => 'nullable|date',
    ]);
    $d->update($data);
    return response()->json(['ok' => true]);
  }

  public function updateStatus($id, Request $r)
  {
    $d = Demanda::findOrFail($id);
    $r->validate(['status' => 'required|in:ABERTA,EM_ANDAMENTO,CONCLUIDA,CANCELADA']);

    $d->status = $r->status;
    $d->concluida_em = $r->status === 'CONCLUIDA' ? now() : null;
    $d->save();

    return response()->json(['ok' => true]);
  }

  public function destroy($id)
  {
    Demanda::whereKey($id)->delete();
    return response()->json(['ok' => true]);
  }
}
