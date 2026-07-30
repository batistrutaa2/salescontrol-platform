<?php

namespace App\Http\Controllers\pages\comercial;

use App\Enums\Tabulations;
use App\Enums\TipoDemandaContrato;
use App\Enums\TipoSolicitacaoPosVenda;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Comentarios;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Demanda;
use App\Models\Dependentes;
use App\Models\Faq;
use App\Models\LogPreditiva;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\PosVendaSolicitacao;
use App\Models\Preditiva;
use App\Models\PreditivaConfiguracao;
use App\Models\PreditivaTabulacaoHard;
use App\Models\User;
use App\Models\Vendas;
use App\Modules\Ranking\Ranking;
use App\Notifications\DemandaVendedorConcluida;
use App\Notifications\DemandaVendedorCriada;
use App\Repositories\Contracts\AgendamentoRepositoryInterface;
use App\Repositories\Contracts\ComentariosLegadosRepositoryInterface;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\LeadAtividadeRepositoryInterface;
use App\Repositories\Contracts\LogPreditivaRepositoryInterface;
use App\Repositories\Contracts\PosVendaSolicitacaoRepositoryInterface;
use App\Repositories\Contracts\PreditivaRegraRepositoryInterface;
use App\Repositories\Contracts\PreditivaRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\TransferenciaContatoRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\AgendamentoRepository;
use App\Repositories\Eloquent\ComentariosLegadosRepository;
use App\Repositories\Eloquent\ComentariosRepository;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\LeadAtividadeRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\TransferenciaContatoRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Eloquent\VendasRepository;
use App\UseCases\ComercialUseCase;
use App\UseCases\MailingUseCase;
use App\UseCases\PreditivaPriorizacaoUseCase;
use App\UseCases\PreditivaUseCase;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

    protected PreditivaPriorizacaoUseCase $preditivaPriorizacaoUseCase;

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
        PreditivaRegraRepositoryInterface $preditivaRegraRepositoryInterface,

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
        $this->preditivaPriorizacaoUseCase = new PreditivaPriorizacaoUseCase($preditivaRegraRepositoryInterface);
        //raking de vendas
        $this->ranking = new Ranking();
    }

    public function index()
    {
        $vendedores = $this->usuariosRepository->getUsersFilterType(Auth::user()->empresa_id, UserRole::VENDEDOR);

        $subTabulacoes = $this->tabulacoesRepository->getSubTabulations(Auth::user()->empresa_id);

        $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);

        return view('content.pages.comercial.index', [
            'vendedores' => $vendedores,
            'typeUserLogeed' => Auth::user()->role->tipo_usuario,
            'subTabulacoes' => $subTabulacoes,
            'tabulacoes' => $tabulacoes,
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
                    'tipo-lead' => $contact->is_ads === 'Y' ? 'R' : 'A',
                    'data_create' => $contact->created_at,
                    'data_update' => $contact->updated_at,
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
                'item' => $items,
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
                    'message' => 'Status atualizado com sucesso.',
                    'marco' => $this->marcoNegociacao((int) $request->tabulacao_id, (int) $request->contato_id),
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'error' => true,
                    'message' => 'Erro ao atualizar status.',
                ],
                501
            );
        }
    }

    /**
     * Monta o "marco" de negociação para o mascote parabenizar quando o lead avança
     * para Reunião, Negociação ou Documento. Retorna null para qualquer outra etapa.
     */
    private function marcoNegociacao(int $tabulacaoId, int $contatoId): ?array
    {
        $etapas = [
            Tabulations::REUNIÃO => 'Reunião',
            Tabulations::NEGOCIAÇÃO => 'Negociação',
            Tabulations::DOCUMENTO => 'Documentos',
        ];

        if (! isset($etapas[$tabulacaoId])) {
            return null;
        }

        $contato = DB::table('contatos')
            ->where('id', $contatoId)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->first(['nome_cliente', 'created_at']);

        if (! $contato) {
            return null;
        }

        return [
            'cliente' => $contato->nome_cliente,
            'etapa' => $etapas[$tabulacaoId],
            'desde' => $contato->created_at ? Carbon::parse($contato->created_at)->format('d/m/Y') : null,
        ];
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

            $dependencies->nome = $request->dependentes[$request->index_array]['nome'];
            $dependencies->cpf = $request->dependentes[$request->index_array]['cpf'];
            $dependencies->idade = $request->dependentes[$request->index_array]['idade'];
            $dependencies->telefone_1 = $request->dependentes[$request->index_array]['telefone1'];
            $dependencies->telefone_2 = $request->dependentes[$request->index_array]['telefone2'];
            $dependencies->telefone_3 = $request->dependentes[$request->index_array]['telefone3'];
            $dependencies->valor_plano = Helpers::moneyForRealSaveBank($request->dependentes[$request->index_array]['valor_plano']);
            if ($dependencies->save()) {
                return redirect()->back()->with('status', 'success')->with('message', 'Dependente: '.$request->dependentes[$request->index_array]['nome'].' Atualizado com sucesso');
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
            'id_mailing' => 'required|integer',
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
        $dependentes = '';
        $clientInfo = $this->repositoryContatosCorretores->getClientInfo($id_mailing);
        $totalFamilyPlan = $clientInfo->valor_plano_atual ?? 0;
        if ($clientInfo->tipo_layout != 'padrao') {
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
        if (Auth::user()->role->id === UserRole::ADMINISTRATIVO || Auth::user()->role->id === UserRole::DEVELOPER || Auth::user()->role->id === UserRole::SUPERVISOR || Auth::user()->role->id === UserRole::VENDEDOR) {
            $permiteEdition = true;
        }
        $cotacoes = [];
        $cotacoesPath = 'cotacoes/'.Auth::user()->empresa_id.'/'.$id_mailing;
        if (Storage::disk('public')->exists($cotacoesPath)) {
            foreach (Storage::disk('public')->files($cotacoesPath) as $file) {
                $cotacoes[] = [
                    'name' => basename($file),
                    'url' => Storage::url($file),
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
                'operadoras' => $operadoras,
            ]);
        }
    }

    public function uploadCotacao(Request $request, $id_mailing)
    {
        $request->validate([
            'file' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $empresaId = Auth::user()->empresa_id;
        $path = "cotacoes/{$empresaId}/{$id_mailing}";

        if ($request->filled('replace')) {
            Storage::disk('public')->delete($path.'/'.$request->input('replace'));
        }

        $file = $request->file('file');
        $filename = time().'_'.$file->getClientOriginalName();
        $file->storeAs($path, $filename, 'public');

        return response()->json([
            'name' => $filename,
            'url' => Storage::url($path.'/'.$filename),
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
            'updated_at' => now(),
        ]);

        if ($saveComment) {
            return response()->json(
                [
                    'error' => false,
                    'message' => 'Comentario feito com sucesso.',
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'error' => true,
                    'message' => 'Erro ao salvar comentario',
                ],
                501
            );
        }
    }

    public function togglePinComment($id)
    {
        $comentario = $this->comentariosRepository->togglePinComment($id);

        if (! $comentario) {
            return response()->json([
                'error' => true,
                'message' => 'Voce so pode fixar comentarios feitos por voce.',
            ], 403);
        }

        return response()->json([
            'error' => false,
            'fixado' => $comentario->fixado === 'Y',
            'message' => $comentario->fixado === 'Y' ? 'Comentario fixado.' : 'Comentario desafixado.',
        ], 200);
    }

    public function updateComment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'anotacao' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => 'Preencha o comentario antes de salvar.'], 422);
        }

        $comentario = $this->comentariosRepository->updateOwnComment($id, $request->anotacao);

        if (! $comentario) {
            return response()->json([
                'error' => true,
                'message' => 'Voce so pode editar comentarios feitos por voce.',
            ], 403);
        }

        return response()->json([
            'error' => false,
            'message' => 'Comentario atualizado com sucesso.',
        ], 200);
    }

    public function remarketing()
    {
        $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
        $tabulations = $this->tabulacoesRepository->getAll(Auth::user()->empresa_id);

        return view('content.pages.comercial.remarketing', [
            'users' => $users,
            'tabulations' => $tabulations,
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
            'users' => $user,
        ]);
    }

    public function transferContact(Request $request)
    {
        try {
            $existePreditiva = DB::table('preditiva')
                ->where('contato_id', $request->idMailing)
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
                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => 'Transferencia concluida com sucesso']);
                }

                return redirect()->back()->with('status', 'success')->with('message', 'Transferencia concluida com sucesso');
            } else {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Erro ao efetuar transferencia de lead'], 500);
                }

                return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead');
            }
        } catch (\Throwable $th) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Erro ao efetuar transferencia de lead.'], 500);
            }

            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferencia de lead.');
        }
    }

    public function transferContactInNulk(Request $request)
    {
        try {
            $leadIds = explode(',', $request->selectedLeadIds);
            array_map(function ($leadId) use ($request) {
                // Limpar preditiva se existir
                DB::table('preditiva')->where('contato_id', $leadId)->delete();

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
                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => 'Transferência concluída com sucesso']);
                }

                return redirect()->back()->with('status', 'success')->with('message', 'Transferência concluída com sucesso');
            } else {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Erro ao efetuar transferência de lead'], 500);
                }

                return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferência de lead');
            }
        } catch (\Throwable $th) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Erro ao efetuar transferência de lead'], 500);
            }

            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao efetuar transferência de lead');
        }
    }

    public function getCommentsLegacy(string $cpf)
    {
        if (Auth::user()->empresa_id === 2) {
            $commentsLegacy = $this->comentariosLegadosRepository->getCommentsLegacy(Helpers::cleanSpecialCharacters($cpf));

            return response()->json($commentsLegacy);
        } else {
            $commentsLegacy = $this->comentariosLegadosRepository->getCommentsLegacy(Helpers::cleanSpecialCharacters(''));

            return response()->json($commentsLegacy);
        }
    }

    public function createSale(Request $request)
    {
        try {
            // Validação dos campos
            $rules = [
                'contato_id' => 'required|integer',
                'nome_contrato' => 'required|string|max:255',
                'cpf_cnpj' => 'required|string',
                'operadora_id' => 'required|integer',
                'titulares' => 'required|array|min:1',

                'titulares.*.nome' => 'required|string|max:100',
                'titulares.*.cpf' => ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/'],
                'titulares.*.data_nascimento' => 'required|date_format:d/m/Y',
                'titulares.*.email' => 'required|email|distinct:ignore_case',
                'titulares.*.telefone1' => 'required|string|min:14',
                'titulares.*.cargo' => 'required_if:tipo_contrato,PME',
                'titulares.*.plano_id' => 'required|integer',
                'titulares.*.coparticipacao' => 'required',
                'titulares.*.plano_anterior' => 'nullable|in:SIM,NAO',
                'titulares.*.operadora_anterior_id' => 'nullable|integer|required_if:titulares.*.plano_anterior,SIM',

                'titulares.*.dependentes' => 'sometimes|array',
                'titulares.*.dependentes.*.nome' => 'required|string|max:100',
                'titulares.*.dependentes.*.cpf' => ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/'],
                'titulares.*.dependentes.*.data_nascimento' => 'required|date_format:d/m/Y',
                'titulares.*.dependentes.*.email' => 'required|email',
                'titulares.*.dependentes.*.telefone1' => 'required|string|min:14',
                'titulares.*.dependentes.*.parentesco' => 'required|string',
                'titulares.*.dependentes.*.plano_anterior' => 'nullable|in:SIM,NAO',
                'titulares.*.dependentes.*.operadora_anterior_id' => 'nullable|integer|required_if:titulares.*.dependentes.*.plano_anterior,SIM',
            ];

            $messages = [
                'contato_id.required' => 'Contato não identificado.',
                'nome_contrato.required' => 'Razão Social é obrigatória.',
                'cpf_cnpj.required' => 'CNPJ é obrigatório.',
                'operadora_id.required' => 'Selecione uma operadora.',
                'titulares.required' => 'Adicione pelo menos um titular.',
                'titulares.min' => 'Adicione pelo menos um titular.',

                'titulares.*.nome.required' => 'Informe o nome completo de todos os titulares.',
                'titulares.*.cpf.required' => 'Informe o CPF de todos os titulares.',
                'titulares.*.cpf.regex' => 'CPF do titular inválido (formato 000.000.000-00).',
                'titulares.*.data_nascimento.required' => 'Informe a data de nascimento de todos os titulares.',
                'titulares.*.data_nascimento.date_format' => 'Data de nascimento do titular inválida (DD/MM/AAAA).',
                'titulares.*.email.required' => 'Informe o e-mail de todos os titulares.',
                'titulares.*.email.email' => 'E-mail de titular inválido.',
                'titulares.*.email.distinct' => 'Dois titulares não podem ter o mesmo e-mail.',
                'titulares.*.telefone1.required' => 'Informe o telefone de todos os titulares.',
                'titulares.*.telefone1.min' => 'Telefone de titular incompleto.',
                'titulares.*.cargo.required_if' => 'Selecione o cargo de todos os titulares.',
                'titulares.*.plano_id.required' => 'Selecione o plano para todos os titulares.',
                'titulares.*.coparticipacao.required' => 'Selecione a coparticipação para todos os titulares.',
                'titulares.*.operadora_anterior_id.required_if' => 'Informe a operadora anterior do titular com plano anterior.',

                'titulares.*.dependentes.*.nome.required' => 'Informe o nome completo de todos os dependentes.',
                'titulares.*.dependentes.*.cpf.required' => 'Informe o CPF de todos os dependentes.',
                'titulares.*.dependentes.*.cpf.regex' => 'CPF de dependente inválido (formato 000.000.000-00).',
                'titulares.*.dependentes.*.data_nascimento.required' => 'Informe a data de nascimento de todos os dependentes.',
                'titulares.*.dependentes.*.data_nascimento.date_format' => 'Data de nascimento de dependente inválida (DD/MM/AAAA).',
                'titulares.*.dependentes.*.email.required' => 'Informe o e-mail de todos os dependentes.',
                'titulares.*.dependentes.*.email.email' => 'E-mail de dependente inválido.',
                'titulares.*.dependentes.*.telefone1.required' => 'Informe o telefone de todos os dependentes.',
                'titulares.*.dependentes.*.telefone1.min' => 'Telefone de dependente incompleto.',
                'titulares.*.dependentes.*.parentesco.required' => 'Selecione o parentesco de todos os dependentes.',
                'titulares.*.dependentes.*.operadora_anterior_id.required_if' => 'Informe a operadora anterior do dependente com plano anterior.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            // Telefones de titulares não podem se repetir; compara só os dígitos
            // porque a máscara pode variar entre restores de old input.
            $validator->after(function ($v) use ($request) {
                $seen = [];
                foreach ((array) $request->input('titulares', []) as $i => $titular) {
                    $digits = preg_replace('/\D/', '', $titular['telefone1'] ?? '');
                    if ($digits === '') {
                        continue;
                    }
                    if (isset($seen[$digits])) {
                        $v->errors()->add("titulares.$i.telefone1", 'Dois titulares não podem ter o mesmo telefone.');
                    } else {
                        $seen[$digits] = $i;
                    }
                }
            });

            $validator->validate();

            $saveSale = $this->vendasRepository->create($request->all());

            if (! $saveSale) {
                return redirect()->back()
                    ->withInput()
                    ->with('status', 'error')
                    ->with('message', 'Falha ao salvar a venda. Verifique os dados e tente novamente.');
            }

            // Venda salva: marca o parabéns do mascote para a próxima página (vendedor).
            // Consumido via session()->pull('mascote_parabens') no partial do mascote.
            session()->put('mascote_parabens', [
                'contrato' => (string) $request->nome_contrato,
                'vidas' => is_array($request->titulares) ? count($request->titulares) : null,
            ]);

            $arrayData = [
                'contato_id' => $request->contato_id,
                'tabulacao_id' => Tabulations::VENDA,
            ];
            $updateStatus = $this->repositoryContatosCorretores->changeStatusLead($arrayData);

            if ($saveSale && $updateStatus) {
                return redirect()->route('sale.listSale')
                    ->with('status', 'success')
                    ->with('message', 'Venda Cadastrada com sucesso');
            }

            return redirect()->route('sale.listSale')
                ->with('status', 'error')
                ->with('message', 'Venda salva, mas houve falha ao atualizar status do lead.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Verifique os campos obrigatórios.');
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Falha ao Cadastrar Venda: '.$th->getMessage());
        }
    }

    public function novaProposta($contato_id)
    {
        $clientInfo = $this->repositoryContatosCorretores->getClientInfo($contato_id);

        if (! $clientInfo || Auth::user()->empresa_id != $clientInfo->empresa_id) {
            return redirect()->route('comercial.kanban')
                ->with('status', 'error')
                ->with('message', 'Sem permissão de acesso');
        }

        $operadoras = Operadora::where('status', 'Y')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('content.pages.comercial.nova-proposta', [
            'client' => $clientInfo,
            'operadoras' => $operadoras,
        ]);
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
            return redirect()->route(route: 'comercial.kanban')->with('status', 'success')->with('message', 'Contato descartado com sucesso');
        } else {
            return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', 'Erro ao descartado com sucesso');
        }
    }

    public function sendSchedule(Request $request)
    {
        try {
            $updateContact = $this->repositoryContatosCorretores->sendSchedule($request->contato_id);
            $sendSchecule = $this->agendamentoRepository->updateOrCreate($request->contato_id, $request->horario_agendamento, $request->observacao);

            if ($updateContact && $sendSchecule) {
                return redirect()->route(route: 'comercial.kanban')->with('status', 'success')->with('message', 'Agendamento efetuado com sucesso');
            } else {
                return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', 'Erro ao salvar agendamento');
            }
        } catch (\Throwable $th) {
            return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', 'Erro ao Agendar contato');
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
            'tabulacoes' => $tabulacoes,
        ]);
    }

    public function getSchedules()
    {
        $schedules = $this->agendamentoRepository->getSchedules(Auth::user()->user_role_id);

        return response()->json([
            'data' => $schedules,
        ]);
    }

    public function searchPendingAppointments()
    {
        if (! request()->ajax()) {
            if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
                return redirect()->intended('comercial/kanban');
            } else {
                return redirect()->intended('dashboard');
            }
        }

        $agendamentos = $this->agendamentoRepository->appointmentsDelaystonotify();

        return response()->json($agendamentos);
    }

    /**
     * Sugestao de contato para o mascote assistente (vendedor): leads do proprio
     * vendedor sem atividade ha N dias, do mais parado ao menos. Reusa o motor de
     * "ultima atividade" da reciclagem de leads frios.
     */
    public function sugestaoContato()
    {
        if (! request()->ajax()) {
            return redirect()->intended('comercial/kanban');
        }

        if (Auth::user()->user_role_id != UserRole::VENDEDOR) {
            return response()->json(['data' => []]);
        }

        $dias = (int) request()->integer('dias', 5);
        if ($dias < 1) {
            $dias = 5;
        }

        $sugestoes = $this->contatosRepository->getSugestoesContato(
            Auth::user()->id,
            Auth::user()->empresa_id,
            $dias,
            10
        );

        return response()->json(['data' => $sugestoes]);
    }

    /**
     * Avisos do mascote: mudanças de status da proposta feitas pelo backoffice
     * (notificacao StatusPropostaAlterada, nao lidas). Substitui o antigo aviso
     * por WhatsApp. So para vendedor.
     */
    public function avisosMascote()
    {
        if (! request()->ajax()) {
            return redirect()->intended('comercial/kanban');
        }

        // Tipos exibidos pelo mascote: vendedor (status da proposta + conclusão de
        // demanda dele) e admin/back-office (nova demanda aberta por vendedor).
        $tiposMascote = [
            'status_venda',
            'venda_estornada',
            'demanda_vendedor_nova',
            'demanda_vendedor_concluida',
        ];

        $avisos = Auth::user()->unreadNotifications
            ->filter(fn ($n) => in_array($n->data['tipo'] ?? null, $tiposMascote, true))
            ->take(5)
            ->map(fn ($n) => [
                'id' => $n->id,
                'tipo' => $n->data['tipo'] ?? 'status_venda',
                'titulo' => $n->data['titulo'] ?? 'Atualização da sua proposta',
                'mensagem' => $n->data['mensagem'] ?? '',
                'status' => $n->data['status'] ?? null,
                'url' => $n->data['url'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $avisos]);
    }

    /**
     * Marca um aviso (notificacao) do mascote como lido.
     */
    public function marcarAvisoLido(string $id)
    {
        if (! request()->ajax()) {
            return response()->json(['ok' => false], 400);
        }

        $notificacao = Auth::user()->notifications()->where('id', $id)->first();
        if ($notificacao) {
            $notificacao->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    public function backQueue(Request $request)
    {
        try {
            if ($request->filled('anotacao')) {
                $this->comentariosRepository->createComment(
                    Auth::user()->empresa_id,
                    Auth::user()->id,
                    $request->anotacao,
                    $request->contato_id
                );
            }

            $deleteSchedule = $this->agendamentoRepository->deleteSchedule($request->contato_id);
            $backQueueLead = $this->repositoryContatosCorretores->changeStatusLead($request->all());

            if ($deleteSchedule && $backQueueLead) {
                return redirect()->route(route: 'comercial.kanban')->with('status', 'success')->with('message', 'Contato concluído e retornado ao funil com sucesso');
            } else {
                return redirect()->route(route: 'comercial.kanban')->with('status', 'error')->with('message', 'Erro ao enviar para fila, contate nosso suporte.');
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao enviar para fila: '.$th->getMessage());
        }
    }

    public function indexMarketing()
    {
        $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
        $tabulations = $this->tabulacoesRepository->getAll(Auth::user()->empresa_id);

        return view('content.pages.comercial.filaMarketing', [
            'users' => $users,
            'tabulations' => $tabulations,
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
                'temperatura' => 'QUENTE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($saveLeadBroker) {
                return redirect()->back()->with('status', 'success')->with('message', 'Lead Enviado com sucesso');
            } else {
                return redirect()->back()->with('status', 'error')->with('message', 'Erro ao enviar lead');
            }

        } catch (\Throwable $th) {
            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao enviar lead');
        }
    }

    public function sendLeadPredictive(Request $request)
    {
        try {
            $respose = $this->preditivaUseCase->sendMailingPredictive($request->id);
            if ($respose) {
                return redirect()->back()->with('status', 'success')->with('message', 'Lead Enviado com sucesso');
            } else {
                return redirect()->back()->with('status', 'error')->with('message', 'Erro ao enviar lead para preditiva');
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao enviar lead para preditiva');
        }
    }

    public function sendMultipleLeadsPredictive(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum lead selecionado',
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
                    'message' => "$successCount lead(s) enviado(s) com sucesso para a fila preditiva.",
                ]);
            } elseif ($successCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "$successCount lead(s) enviado(s) com sucesso e $errorCount com falha.",
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Falha ao enviar todos os $errorCount lead(s) para a fila preditiva.",
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar: '.$th->getMessage(),
            ], 500);
        }
    }

    public function getClientesPreditiva(Request $request)
    {
        $userId = Auth::id();
        $empresaId = Auth::user()->empresa_id;

        // Liberar locks expirados deste usuário (mais de 2h sem ação)
        Preditiva::where('user_id', $userId)
            ->where('data_atribuicao', '<', now()->subHours(2))
            ->update(['user_id' => null, 'data_atribuicao' => null]);

        $scoreExpression = $this->preditivaPriorizacaoUseCase->getScoreExpression($empresaId);
        $config = PreditivaConfiguracao::getOrDefault($empresaId);
        $maxTentativas = 20;

        for ($tentativa = 0; $tentativa < $maxTentativas; $tentativa++) {
            $eid = (int) $empresaId;
            $cooldownHoras = (int) $config->cooldown_horas;

            $cliente = DB::table('preditiva as p')
                ->join('contatos as c', 'p.contato_id', '=', 'c.id')
                ->leftJoin(DB::raw("(
                  SELECT
                    contato_id,
                    SUM(CASE WHEN tipo_descarte = 'SOFT' OR tipo_descarte IS NULL THEN 1 ELSE 0 END) AS descartes_soft,
                    SUM(CASE WHEN tipo_descarte = 'HARD' THEN 1 ELSE 0 END) AS descartes_hard,
                    MAX(created_at) AS ultimo_descarte
                  FROM log_preditiva
                  WHERE acao = 'DESCARTE' AND empresa_id = {$eid}
                  GROUP BY contato_id
              ) as lp"), 'p.contato_id', '=', 'lp.contato_id')
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
                    'c.created_at',
                    DB::raw($scoreExpression),
                    // Cooldown como prioridade: 0 = fora do cooldown (preferido), 1 = ainda em cooldown
                    // Leads em cooldown são despriorizados mas não excluídos — evita vitrine vazia
                    DB::raw("CASE
            WHEN {$cooldownHoras} = 0 THEN 0
            WHEN lp.ultimo_descarte IS NULL THEN 0
            WHEN lp.ultimo_descarte < NOW() - INTERVAL {$cooldownHoras} HOUR THEN 0
            ELSE 1
          END AS em_cooldown")
                )
                ->where('p.empresa_id', $empresaId)
                ->where('p.status', 'Y')
                ->where(function ($q) use ($userId) {
                    $q->whereNull('p.user_id')->orWhere('p.user_id', $userId);
                })
                ->whereNotNull('c.cpf')
                ->where('c.cpf', '!=', '')
              // Filtro: limites por tipo de descarte
                ->where(function ($q) use ($config) {
                    $q->whereNull('lp.contato_id')
                        ->orWhere(function ($q2) use ($config) {
                            $q2->where(function ($q3) use ($config) {
                                $q3->whereNull('lp.descartes_soft')
                                    ->orWhere('lp.descartes_soft', '<', $config->limite_descartes_soft);
                            })->where(function ($q3) use ($config) {
                                $q3->whereNull('lp.descartes_hard')
                                    ->orWhere('lp.descartes_hard', '<', $config->limite_descartes_hard);
                            });
                        });
                })
              // Filtro: exclusão por usuário (permanente ou com prazo configurável)
                ->whereNotExists(function ($q) use ($userId, $empresaId, $config) {
                    $q->select(DB::raw(1))
                        ->from('log_preditiva')
                        ->whereColumn('log_preditiva.contato_id', 'p.contato_id')
                        ->where('log_preditiva.user_id', $userId)
                        ->where('log_preditiva.empresa_id', $empresaId)
                        ->where('log_preditiva.acao', 'DESCARTE')
                        ->when(
                            ! is_null($config->exclusao_usuario_dias),
                            fn ($q2) => $q2->where(
                                'log_preditiva.created_at',
                                '>=',
                                now()->subDays($config->exclusao_usuario_dias)
                            )
                        );
                })
                ->orderBy('em_cooldown', 'asc')       // leads fora do cooldown primeiro
                ->orderByDesc('score_priorizacao')
                ->orderBy('lp.ultimo_descarte', 'asc')
                ->orderBy('p.created_at', 'asc')
                ->limit(1)
                ->first();

            if (! $cliente) {
                break;
            }

            // CPF inválido: descarte HARD automático — remove e continua no loop
            if (empty($cliente->cpf) || is_null($cliente->cpf)) {
                Preditiva::where('contato_id', $cliente->id)
                    ->where('empresa_id', $empresaId)
                    ->delete();

                LogPreditiva::create([
                    'empresa_id' => $empresaId,
                    'user_id' => $userId,
                    'contato_id' => $cliente->id,
                    'tabulacao' => 'CPF VAZIO/INVALIDO',
                    'acao' => 'DESCARTE',
                    'tipo_descarte' => 'HARD',
                ]);

                continue;
            }

            // Atribuição atômica: atualiza apenas se o lead ainda está disponível.
            // Evita race condition entre vendedores simultâneos — se affected rows = 0,
            // outro vendedor pegou o lead antes; o loop tenta o próximo candidato.
            $atualizado = DB::table('preditiva')
                ->where('contato_id', $cliente->id)
                ->where('empresa_id', $empresaId)
                ->where('status', 'Y')
                ->where(function ($query) use ($userId) {
                    $query->whereNull('user_id')
                        ->orWhere('user_id', $userId);
                })
                ->update([
                    'user_id' => $userId,
                    'data_atribuicao' => now(),
                ]);

            if ($atualizado === 0) {
                continue;
            }

            $dependentes = Dependentes::where('contato_id', $cliente->id)
                ->select('id', 'nome', 'cpf', 'idade', 'parentesco', 'telefone_1', 'telefone_2', 'telefone_3', 'valor_plano')
                ->get();

            $clienteData = (array) $cliente;
            $clienteData['dependentes'] = $dependentes;

            return response()->json([$clienteData]);
        }

        return response()->json([]);
    }

    public function descartarClientePreditiva(Request $request)
    {
        $userId = Auth::id();
        $empresaId = Auth::user()->empresa_id;
        $contatoId = $request->contato_id;
        $tabulacao = strtoupper(trim($request->tabulacao ?? 'SEM TABULACAO'));

        // Carrega configurações da empresa (com defaults seguros se não existir)
        $config = PreditivaConfiguracao::getOrDefault($empresaId);

        // Determina o tipo: HARD remove imediatamente, SOFT acumula até o limite
        $isHard = PreditivaTabulacaoHard::isHard($empresaId, $tabulacao);
        $tipoDescarte = $isHard ? 'HARD' : 'SOFT';

        LogPreditiva::create([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'contato_id' => $contatoId,
            'tabulacao' => $tabulacao,
            'acao' => 'DESCARTE',
            'tipo_descarte' => $tipoDescarte,
        ]);

        // Carimbo durável: vendedor trabalhou o lead na preditiva. Sobrevive ao Limpar Fila
        // (mora em contatos, nao em log_preditiva) e alimenta o "ultimo contato" da visualizar-leads.
        Contatos::where('id', $contatoId)
            ->where('empresa_id', $empresaId)
            ->update(['ultimo_contato_preditiva' => now()]);

        // HARD: remoção imediata — cliente atendeu e disse que não tem interesse
        if ($isHard) {
            Preditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->delete();

            Contatos::where('id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->update(['status' => 'N', 'updated_at' => now()]);

            return response()->json([
                'success' => true,
                'desativado' => true,
                'tipo' => 'HARD',
                'message' => 'Lead removido definitivamente da preditiva.',
            ]);
        }

        // SOFT: contabiliza e verifica se atingiu o limite configurado
        // Legado (tipo_descarte NULL) é tratado como SOFT para compatibilidade
        $totalSoft = DB::table('log_preditiva')
            ->where('contato_id', $contatoId)
            ->where('empresa_id', $empresaId)
            ->where('acao', 'DESCARTE')
            ->where(function ($q) {
                $q->where('tipo_descarte', 'SOFT')->orWhereNull('tipo_descarte');
            })
            ->count();

        if ($totalSoft >= $config->limite_descartes_soft) {
            Preditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->delete();

            Contatos::where('id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->update(['status' => 'N', 'updated_at' => now()]);

            return response()->json([
                'success' => true,
                'desativado' => true,
                'tipo' => 'SOFT',
                'message' => "Lead atingiu {$config->limite_descartes_soft} tentativas sem sucesso e foi removido da preditiva.",
            ]);
        }

        // Libera o lock — o cooldown é aplicado na próxima busca, não aqui
        Preditiva::where('contato_id', $contatoId)
            ->update(['user_id' => null, 'data_atribuicao' => null]);

        $restantes = $config->limite_descartes_soft - $totalSoft;

        return response()->json([
            'success' => true,
            'desativado' => false,
            'tipo' => 'SOFT',
            'tentativas_restantes' => $restantes,
        ]);
    }

    public function converterClientePreditiva(Request $request)
    {
        $userId = Auth::id();
        $empresaId = Auth::user()->empresa_id;
        $contatoId = $request->contato_id;

        LogPreditiva::create([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'contato_id' => $contatoId,
            'tabulacao' => $request->tabulacao ?? 'CONVERTIDO',
            'acao' => 'CONVERSAO',
        ]);

        // Carimbo durável: vendedor trabalhou o lead na preditiva (conversao).
        Contatos::where('id', $contatoId)
            ->where('empresa_id', $empresaId)
            ->update(['ultimo_contato_preditiva' => now()]);

        $existingRecord = DB::table('contatos_corretores')
            ->where('contato_id', $contatoId)
            ->where('user_id', $userId)
            ->first();

        if (! $existingRecord) {
            DB::table('contatos_corretores')->insert([
                'empresa_id' => $empresaId,
                'contato_id' => $contatoId,
                'user_id' => $userId,
                'tabulacao_id' => Tabulations::PROSPECCAO,
                'sub_tabulacao_id' => null,
                'temperatura' => 'FRIO',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Jobs\Whatsapp\RevincularConversasContato::dispatch((int) $contatoId, (int) $userId);
        }

        Preditiva::where('contato_id', $contatoId)->delete();

        Comentarios::where('user_id', $userId)
            ->where('contato_id', $contatoId)
            ->where('empresa_id', $empresaId)
            ->update([
                'visivel' => 'N',
            ]);

        return response()->json(['success' => true]);
    }

    public function descartarCliente($id_mailing)
    {
        try {
            DB::beginTransaction();

            $deleteLinked = $this->repositoryContatosCorretores->deleteMailing($id_mailing);

            if (! $deleteLinked) {
                throw new \Exception('Erro ao excluir mailing.');
            }

            $updateCustomer = Contatos::where('id', $id_mailing)->update([
                'status' => 'N',
                'updated_at' => now(),
            ]);

            if (! $updateCustomer) {
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
        $empresaId = Auth::user()->empresa_id;

        try {
            DB::transaction(function () use ($ids, $clearPreditiva, $empresaId) {
                foreach ($ids as $id) {
                    if ($clearPreditiva) {
                        DB::table('preditiva')
                            ->where('contato_id', $id)
                            ->where('empresa_id', $empresaId)
                            ->delete();

                        DB::table('log_preditiva')
                            ->where('contato_id', $id)
                            ->where('empresa_id', $empresaId)
                            ->delete();
                    }

                    $this->repositoryContatosCorretores->deleteMailing($id);

                    Contatos::where('id', $id)
                        ->where('empresa_id', $empresaId)
                        ->update([
                            'status' => 'N',
                            'updated_at' => now(),
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
        // Origem: TODOS (default), INTERNA (tarefas do setor) ou VENDEDOR (solicitações de pós-venda).
        $origem = $request->input('origem', 'TODOS');

        $q = Demanda::with(['criador:id,name', 'responsavel:id,name', 'venda:id,nome_contrato,numero_proposta'])
            ->where('empresa_id', Auth::user()->empresa_id)
            ->when($origem === 'INTERNA' || $origem === 'VENDEDOR', fn ($s) => $s->where('origem', $origem))
            ->when($request->has('status') && $request->status !== 'TODOS', fn ($s) => $s->where('status', $request->status))
            ->when($request->has('prioridade') && $request->prioridade !== 'TODAS', fn ($s) => $s->where('prioridade', $request->prioridade))
            ->when($request->has('assigned_to') && $request->assigned_to, fn ($s) => $s->where('assigned_to', $request->assigned_to))
            ->where(function ($query) {
                $query->where('status', '!=', 'CONCLUIDA')
                    ->orWhere(function ($q) {
                        $q->where('status', 'CONCLUIDA')
                            ->where('concluida_em', '>=', now()->subDays(30));
                    });
            })
            ->orderByDesc('created_at');

        return response()->json([
            'data' => $q->get()->map(function ($d) {
                return [
                    'id' => $d->id,
                    'origem' => $d->origem,
                    'titulo' => $d->titulo,
                    'descricao' => $d->descricao,
                    'prioridade' => $d->prioridade,
                    'status' => $d->status,
                    'responsavel' => $d->responsavel?->name,
                    'criador' => $d->criador?->name,
                    // Campos extras das solicitações de vendedor (null nas internas).
                    'tipo' => $d->tipo,
                    'tipo_label' => $d->tipo ? (TipoDemandaContrato::tryFrom($d->tipo)?->label() ?? $d->tipo) : null,
                    'cliente' => $d->venda?->nome_contrato,
                    'numero_proposta' => $d->venda?->numero_proposta,
                    'data_limite' => optional($d->data_limite)->format('Y-m-d'),
                    'created_at' => $d->created_at->toDateTimeString(),
                ];
            }),
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
        $d = Demanda::where('empresa_id', Auth::user()->empresa_id)->findOrFail($id);
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
        $d = Demanda::where('empresa_id', Auth::user()->empresa_id)->findOrFail($id);
        $r->validate(['status' => 'required|in:ABERTA,EM_ANDAMENTO,CONCLUIDA,CANCELADA']);

        $eraConcluida = $d->status === 'CONCLUIDA';

        $d->status = $r->status;
        $d->concluida_em = $r->status === 'CONCLUIDA' ? now() : null;
        $d->save();

        // Solicitação do vendedor recém-concluída -> avisa o vendedor pelo mascote.
        if ($d->origem === 'VENDEDOR' && $r->status === 'CONCLUIDA' && ! $eraConcluida) {
            $this->notificarVendedorDemandaConcluida($d);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy($id)
    {
        Demanda::where('empresa_id', Auth::user()->empresa_id)->whereKey($id)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Notifica o vendedor que abriu a demanda de que ela foi concluída pelo pós-venda.
     */
    private function notificarVendedorDemandaConcluida(Demanda $d): void
    {
        $vendedor = User::find($d->created_by);
        if (! $vendedor) {
            return;
        }

        $cliente = $d->venda?->nome_contrato ?? 'cliente';
        $tipoLabel = $d->tipo ? (TipoDemandaContrato::tryFrom($d->tipo)?->label() ?? $d->titulo) : $d->titulo;

        $vendedor->notify(new DemandaVendedorConcluida(
            demandaId: $d->id,
            cliente: $cliente,
            tipoLabel: $tipoLabel,
            url: route('comercial.demandasVendedor'),
            concluidaPorNome: Auth::user()->name,
        ));
    }

    // ==================== Demandas de Pós-venda (vendedor) ====================

    /**
     * Tela do vendedor para abrir e acompanhar solicitações de pós-venda
     * (boleto, portabilidade, cancelamento, etc.) sobre os contratos que implantou.
     */
    public function demandasVendedor()
    {
        return view('content.pages.comercial.minhas-demandas', [
            'tipoLabels' => TipoSolicitacaoPosVenda::labels(),
        ]);
    }

    /**
     * Lista as solicitações de pós-venda abertas pelo próprio vendedor.
     * Fonte única: a Central de Solicitações do backoffice (pos_venda_solicitacoes).
     */
    public function listMinhasDemandas()
    {
        $demandas = PosVendaSolicitacao::with(['venda:id,nome_contrato,numero_proposta', 'etapa:id,nome'])
            ->where('empresa_id', Auth::user()->empresa_id)
            ->where('origem', PosVendaSolicitacao::ORIGEM_VENDEDOR)
            ->where('created_by', Auth::id())
            ->orderByRaw("FIELD(status, 'ABERTA', 'CONCLUIDA', 'CANCELADA')")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'titulo' => $d->titulo,
                'descricao' => $d->descricao,
                'status' => $d->status,
                'etapa' => $d->etapa?->nome,
                'tipo' => $d->tipo,
                'tipo_label' => TipoSolicitacaoPosVenda::tryFrom($d->tipo)?->label() ?? $d->tipo,
                'cliente' => $d->venda?->nome_contrato,
                'numero_proposta' => $d->venda?->numero_proposta,
                'data_limite' => $d->data_limite?->format('d/m/Y'),
                'created_at' => $d->created_at->toDateTimeString(),
                'concluida_em' => optional($d->concluida_em)->toDateTimeString(),
            ]);

        return response()->json(['data' => $demandas]);
    }

    /**
     * Autocomplete dos contratos IMPLANTADOS do próprio vendedor, para abrir a demanda.
     */
    public function buscarContratosImplantados(Request $request)
    {
        $termo = trim((string) $request->input('termo', ''));

        $contratos = Vendas::query()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->where('user_id', Auth::id())
            ->where('tabulacao_id', Tabulations::IMPLANTADO)
            ->when($termo !== '', function ($q) use ($termo) {
                $q->where(function ($sub) use ($termo) {
                    $sub->where('nome_contrato', 'like', "%{$termo}%")
                        ->orWhere('numero_proposta', 'like', "%{$termo}%")
                        ->orWhere('cpf_cnpj', 'like', "%{$termo}%");
                });
            })
            ->orderByDesc('data_implantacao')
            ->limit(25)
            ->get(['id', 'nome_contrato', 'numero_proposta', 'cpf_cnpj', 'operadora'])
            ->map(fn ($v) => [
                'venda_id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'numero_proposta' => $v->numero_proposta,
                'cpf_cnpj' => $v->cpf_cnpj,
                'operadora' => $v->operadora,
            ]);

        return response()->json(['data' => $contratos]);
    }

    /**
     * Cria a solicitação de pós-venda do vendedor sobre um contrato implantado seu
     * e avisa o administrativo/back-office pelo mascote.
     */
    public function storeDemandaVendedor(Request $request)
    {
        $tipos = array_map(fn ($t) => $t->value, TipoSolicitacaoPosVenda::cases());

        $data = $request->validate([
            'venda_id' => 'required|integer|exists:vendas,id',
            'tipo' => ['required', 'string', \Illuminate\Validation\Rule::in($tipos)],
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:1000',
        ]);

        $empresaId = Auth::user()->empresa_id;

        // Garante que o contrato é do próprio vendedor, da empresa e está IMPLANTADO.
        $venda = Vendas::where('id', $data['venda_id'])
            ->where('empresa_id', $empresaId)
            ->where('user_id', Auth::id())
            ->where('tabulacao_id', Tabulations::IMPLANTADO)
            ->first();

        if (! $venda) {
            return response()->json([
                'ok' => false,
                'message' => 'Contrato inválido: só é possível abrir demanda para um contrato seu já implantado.',
            ], 422);
        }

        // Entra direto na fila da Central de Solicitações do pós-venda, sem prazo
        // (o backoffice define a data-limite na triagem).
        $solicitacao = app(PosVendaSolicitacaoRepositoryInterface::class)->criar($empresaId, [
            'venda_id' => $venda->id,
            'tipo' => $data['tipo'],
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'origem' => PosVendaSolicitacao::ORIGEM_VENDEDOR,
            'observacao_abertura' => 'Solicitação aberta pelo vendedor.',
        ], Auth::id());

        $this->notificarAdminsDemandaVendedor($solicitacao, $venda);

        return response()->json(['ok' => true, 'id' => $solicitacao->id]);
    }

    /**
     * Notifica os usuários administrativos/back-office da empresa sobre a nova
     * solicitação de pós-venda aberta por um vendedor.
     */
    private function notificarAdminsDemandaVendedor(PosVendaSolicitacao $solicitacao, Vendas $venda): void
    {
        $admins = User::where('empresa_id', $solicitacao->empresa_id)
            ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::BACKOFFICE])
            ->where('ativo', 'Y')
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $tipoLabel = TipoSolicitacaoPosVenda::tryFrom($solicitacao->tipo)?->label() ?? $solicitacao->titulo;

        Notification::send($admins, new DemandaVendedorCriada(
            demandaId: $solicitacao->id,
            vendedorNome: Auth::user()->name,
            cliente: $venda->nome_contrato ?? 'cliente',
            tipoLabel: $tipoLabel,
            url: route('backoffice.solicitacoes.index'),
        ));
    }

    /**
     * Analisa comentários do cliente com IA e sugere estratégias de venda
     */
    public function analisarClienteComIA(Request $request)
    {
        try {
            $request->validate([
                'contato_id' => 'required|integer',
            ]);

            $contatoId = $request->contato_id;
            $empresaId = Auth::user()->empresa_id;

            // Buscar dados do cliente
            $cliente = Contatos::where('id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cliente não encontrado.',
                ], 404);
            }

            // Buscar comentários
            $comentarios = Comentarios::select('comentarios.anotacao', 'comentarios.created_at', 'users.name')
                ->leftJoin('users', 'comentarios.user_id', '=', 'users.id')
                ->where('comentarios.contato_id', $contatoId)
                ->where('comentarios.visivel', 'Y')
                ->orderBy('comentarios.created_at', 'asc')
                ->get();

            if ($comentarios->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Nenhum comentário encontrado para análise. Adicione algumas anotações primeiro.',
                ]);
            }

            // Chamar o serviço de IA
            $claudeService = new \App\Services\ClaudeAIService();
            $resultado = $claudeService->analisarClienteESugerirEstrategias(
                $cliente->toArray(),
                $comentarios->toArray()
            );

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar análise: '.$e->getMessage(),
            ], 500);
        }
    }

    public function faqsVendedor()
    {
        $empresaId = Auth::user()->empresa_id;

        $faqs = Faq::with('operadora')
            ->where('empresa_id', $empresaId)
            ->where('status', 'Y')
            ->orderBy('ordem')
            ->orderBy('titulo')
            ->get();

        $faqsPorOperadora = $faqs->groupBy(function ($faq) {
            return $faq->operadora->nome ?? 'Sem Operadora';
        });

        $operadoras = $faqs->pluck('operadora')->unique('id')->filter()->sortBy('nome');

        return view('content.pages.comercial.faqs-vendedor', [
            'faqsPorOperadora' => $faqsPorOperadora,
            'operadoras' => $operadoras,
        ]);
    }
}
