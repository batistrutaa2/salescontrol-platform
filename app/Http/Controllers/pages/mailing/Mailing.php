<?php

namespace App\Http\Controllers\pages\mailing;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Imports\ContatosImportPreditiva;
use App\Models\Agendamento;
use App\Models\Comentarios;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Dependentes;
use App\Models\LeadAtividade;
use App\Models\Ligacoes;
use App\Models\LogPreditiva;
use App\Models\MailingImportacao;
use App\Models\Preditiva;
use App\Models\PreditivaConfiguracao;
use App\Models\PreditivaEnvio;
use App\Models\PreditivaRegraPriorizacao;
use App\Models\PreditivaTabulacaoHard;
use App\Models\TransferenciaContato;
use App\Repositories\Contracts\BaseLegaceRespositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\PreditivaRegraRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\BaseLegaceRespository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\PreditivaRegraRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\VendasRepository;
use App\Services\LeadManagementService;
use App\Services\MailingImportService;
use App\UseCases\MailingUseCase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class Mailing extends Controller
{
    protected UsuariosRepositoryInterface $usuarioRepository;

    protected TabulacoesRepository $tabulacoesRepository;

    protected BaseLegaceRespository $baseLegaceRespository;

    protected MailingUseCase $mailingUseCase;

    protected ContatosRepository $contatosRepository;

    protected VendasRepository $vendasRepository;

    protected PreditivaRegraRepository $preditivaRegraRepository;

    protected LeadManagementService $leadManagementService;

    protected MailingImportService $mailingImportService;

    private $rulesUpload = [
        'base' => 'required|string',
        'file' => 'required|file',
    ];

    public function __construct(
        UsuariosRepositoryInterface $usuariosRepositoryInterface,
        ContatosRepositoryInterface $contatosRepositoryInterface,
        TabulacoesRepositoryInterface $tabulacoesRepositoryInterface,
        BaseLegaceRespositoryInterface $baseLegaceRespositoryInterface,
        VendasRepositoryInterface $vendasRepositoryInterface,
        PreditivaRegraRepositoryInterface $preditivaRegraRepositoryInterface,
        MailingImportService $mailingImportService
    ) {

        $this->mailingUseCase = new MailingUseCase($contatosRepositoryInterface);

        $this->contatosRepository = $contatosRepositoryInterface;
        $this->usuarioRepository = $usuariosRepositoryInterface;
        $this->tabulacoesRepository = $tabulacoesRepositoryInterface;
        $this->baseLegaceRespository = $baseLegaceRespositoryInterface;
        $this->vendasRepository = $vendasRepositoryInterface;
        $this->preditivaRegraRepository = $preditivaRegraRepositoryInterface;
        $this->leadManagementService = app(LeadManagementService::class);
        $this->mailingImportService = $mailingImportService;
    }

    public function index()
    {
        $users = $this->usuarioRepository->getUserByCompany(Auth::user()->empresa_id);
        $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);

        return view('content.pages.mailing.importMailing', [
            'users' => $users,
            'tabulacoes' => $tabulacoes,
        ]);
    }

    public function importaMailing(Request $request)
    {
        try {
            $empresaId = (int) Auth::user()->empresa_id;
            $validated = $request->validate([
                'base' => ['required', 'string', 'max:255'],
                'file' => ['required', 'file', 'max:5120'],
                'tipo_layout' => ['required', Rule::in(['padrao', 'com_dependentes'])],
                'id_user' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('ativo', 'Y')),
                ],
                'tabulacao' => [
                    'required',
                    'integer',
                    Rule::exists('tabulacoes', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('status', 'Y')->where('tipo_tabulacao', 'C')),
                ],
            ]);

            $this->validateFileUploadExcel($request);
            $importacao = $this->mailingImportService->analisar(
                $request->file('file'),
                $empresaId,
                (int) Auth::id(),
                $validated['base'],
                $validated['tipo_layout'],
                (int) $validated['id_user'],
                (int) $validated['tabulacao'],
            );

            return response()->json([
                'error' => false,
                'message' => 'Arquivo analisado. Revise o resultado antes de confirmar.',
                'data' => $this->mailingImportService->detalhar($importacao),
            ], 201);
        } catch (\Throwable $th) {
            if ($th instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => $th->validator->errors()->first(),
                    'errors' => $th->validator->errors(),
                ], 422);
            }
            if ($th instanceof \InvalidArgumentException) {
                return response()->json(['message' => $th->getMessage()], 422);
            }
            Log::error('Falha ao analisar importação de mailing', ['exception' => $th]);

            return response()->json([
                'error' => true,
                'message' => 'Não foi possível analisar o arquivo. Verifique o layout da planilha e tente novamente.',
            ], 500);
        }
    }

    public function importacaoPendente()
    {
        $importacao = MailingImportacao::query()
            ->where('empresa_id', Auth::user()->empresa_id)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['EM_ANALISE', 'PARCIAL'])
            ->latest('updated_at')
            ->first();

        return response()->json([
            'data' => $importacao ? $this->mailingImportService->detalhar($importacao) : null,
        ]);
    }

    public function importarNovos(MailingImportacao $importacao)
    {
        $this->garantirAcessoImportacao($importacao);
        $resultado = $this->mailingImportService->importarNovos($importacao, (int) Auth::id());

        return response()->json([
            'message' => "{$resultado['importados']} lead(s) não duplicado(s) importado(s).",
            'resultado' => $resultado,
            'data' => $this->mailingImportService->detalhar($importacao),
        ]);
    }

    public function resolverDuplicados(Request $request, MailingImportacao $importacao)
    {
        $this->garantirAcessoImportacao($importacao);
        $empresaId = (int) Auth::user()->empresa_id;
        $validated = $request->validate([
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['integer'],
            'destino' => ['required', Rule::in(['VENDEDOR', 'PREDITIVA'])],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('ativo', 'Y')),
            ],
            'tabulacao_id' => [
                'nullable',
                'integer',
                Rule::exists('tabulacoes', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('status', 'Y')->where('tipo_tabulacao', 'C')),
            ],
        ]);

        try {
            $resolvidos = $this->mailingImportService->resolverDuplicados(
                $importacao,
                $validated['itens'],
                $validated['destino'],
                isset($validated['vendedor_id']) ? (int) $validated['vendedor_id'] : null,
                isset($validated['tabulacao_id']) ? (int) $validated['tabulacao_id'] : null,
                (int) Auth::id(),
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => "{$resolvidos} lead(s) atualizado(s) com sucesso.",
            'data' => $this->mailingImportService->detalhar($importacao),
        ]);
    }

    public function cancelarImportacao(MailingImportacao $importacao)
    {
        $this->garantirAcessoImportacao($importacao);
        $importacao->update(['status' => 'CANCELADA', 'concluida_em' => now()]);

        return response()->json(['message' => 'Análise descartada.']);
    }

    private function garantirAcessoImportacao(MailingImportacao $importacao): void
    {
        abort_unless(
            (int) $importacao->empresa_id === (int) Auth::user()->empresa_id
              && (int) $importacao->user_id === (int) Auth::id(),
            404
        );
    }

    public function deleteMailingLeadsDescarted($id)
    {
        try {
            $searchForLaunchedSale = $this->vendasRepository->checkExistenceSale($id);

            if ($searchForLaunchedSale) {
                return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', 'Esse Lead possui venda cadastrada, exclusão cancelada.');
            }

            DB::beginTransaction();
            Comentarios::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Agendamento::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Dependentes::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Ligacoes::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            LeadAtividade::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            ContatosCorretores::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            LogPreditiva::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Preditiva::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            PreditivaEnvio::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            TransferenciaContato::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Contatos::where('id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            DB::commit();

            return redirect()->back()->with('status', 'success')->with('message', 'Contato Excluido com sucesso');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->route(route: 'mailing.leadDescartados')->with('status', 'error')->with('message', 'Erro ao excluir Lead');
        }
    }

    public function deleteMailing($id)
    {
        try {
            $searchForLaunchedSale = $this->vendasRepository->checkExistenceSale($id);

            if ($searchForLaunchedSale) {
                return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', 'Esse Lead possui venda cadastrada, exclusão cancelada.');
            }

            DB::beginTransaction();
            Comentarios::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Agendamento::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Dependentes::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Ligacoes::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            LeadAtividade::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            ContatosCorretores::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            LogPreditiva::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Preditiva::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            PreditivaEnvio::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            TransferenciaContato::where('contato_id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            Contatos::where('id', $id)->where('empresa_id', Auth::user()->empresa_id)->delete();
            DB::commit();

            return redirect()->back()->with('status', 'success')->with('message', 'Contato Excluido com sucesso');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', 'Erro ao excluir Lead');
        }
    }

    public function viewLeads()
    {
        $empresaId = Auth::user()->empresa_id;
        $users = $this->usuarioRepository->getUserByCompany($empresaId);
        $tabulations = $this->tabulacoesRepository->getAll($empresaId);
        $kpis = $this->leadManagementService->getLeadKPIs($empresaId);

        return view('content.pages.mailing.visualizar-leads', [
            'users' => $users,
            'tabulations' => $tabulations,
            'kpis' => $kpis,
        ]);
    }

    public function viewLeadslegacy()
    {

        if (Auth::user()->empresa_id == 2) {
            $contacts = $this->baseLegaceRespository->getContactsAll();

            return view('content.pages.mailing.visualizar-leads-legado', [
                'contatos' => $contacts,
            ]);
        } else {
            return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', 'Acesso negado.');
        }
    }

    public function getLeads()
    {
        $data = $this->contatosRepository->getLeads(Auth::user()->empresa_id);

        return response()->json(['data' => $data]);
    }

    public function getAllLeadsServerSide(Request $request)
    {
        $data = $this->contatosRepository->getAllLeadsServerSide(Auth::user()->empresa_id, $request);

        return response()->json($data);
    }

    public function getLeadKPIs(Request $request)
    {
        $kpis = $this->leadManagementService->getLeadKPIs(Auth::user()->empresa_id, $request);

        return response()->json($kpis);
    }

    public function reactivateLead(Request $request)
    {
        $result = $this->leadManagementService->reactivateLead($request->id, Auth::user()->empresa_id);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Lead reativado com sucesso.' : 'Erro ao reativar lead.',
        ]);
    }

    public function bulkReactivateLeads(Request $request)
    {
        $ids = $request->input('ids', []);
        $result = $this->leadManagementService->bulkReactivateLeads($ids, Auth::user()->empresa_id);

        return response()->json([
            'success' => true,
            'message' => "{$result['success']} leads reativados com sucesso.",
            'details' => $result,
        ]);
    }

    public function bulkDeleteLeads(Request $request)
    {
        $ids = $request->input('ids', []);
        $result = $this->leadManagementService->bulkDeleteLeads($ids, Auth::user()->empresa_id);

        return response()->json([
            'success' => true,
            'message' => "{$result['success']} leads excluidos.",
            'details' => $result,
        ]);
    }

    public function discardLead(Request $request)
    {
        $result = $this->leadManagementService->discardLead($request->id, Auth::user()->empresa_id);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Lead descartado com sucesso.' : 'Erro ao descartar lead.',
        ]);
    }

    public function bulkDiscardLeads(Request $request)
    {
        $ids = $request->input('ids', []);
        $result = $this->leadManagementService->bulkDiscardLeads($ids, Auth::user()->empresa_id);

        return response()->json([
            'success' => true,
            'message' => "{$result['success']} leads descartados.",
            'details' => $result,
        ]);
    }

    public function getLeadsLegacy($id_mailing)
    {
        $infoContact = $this->baseLegaceRespository->getContacts($id_mailing);
        $comments = $this->baseLegaceRespository->getCommentsMailing($id_mailing);

        return response()->json(
            [
                'contato' => $infoContact,
                'comentarios' => $comments,
            ]
        );
    }

    public function contactsAdvertisement()
    {
        return view('content.pages.mailing.leads-anuncio');
    }

    public function preditiva()
    {
        $empresaId = Auth::user()->empresa_id;
        $users = $this->usuarioRepository->getUserByCompany($empresaId);
        $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial($empresaId);
        $config = PreditivaConfiguracao::getOrDefault($empresaId);
        $tabsHard = PreditivaTabulacaoHard::listForEmpresa($empresaId);

        return view('content.pages.mailing.preditiva', [
            'users' => $users,
            'tabulacoes' => $tabulacoes,
            'config' => $config,
            'tabsHard' => $tabsHard,
        ]);
    }

    public function getPreditiva(Request $request)
    {

        $empresaId = Auth::user()->empresa_id;

        $hoje = Carbon::today();

        $totalLeadsFila = DB::table('preditiva')
            ->where('status', 'Y')
            ->where('empresa_id', $empresaId)
            ->count();

        $tentativasHoje = DB::table('log_preditiva')
            ->whereDate('created_at', $hoje)
            ->where('empresa_id', $empresaId)
            ->count();

        $conversoesHoje = DB::table('log_preditiva')
            ->whereDate('created_at', $hoje)
            ->where('acao', 'CONVERSAO')
            ->where('tabulacao', 'CONVERTIDO')
            ->where('empresa_id', $empresaId)
            ->count();

        $recusasHoje = DB::table('log_preditiva')
            ->whereDate('created_at', $hoje)
            ->where('acao', 'DESCARTE')
            ->whereIn('tabulacao', ['JA POSSUI PLANO', 'NAO INTERESSADO', 'NUMERO INEXISTENTE', 'NAO ATENDE'])
            ->where('empresa_id', $empresaId)
            ->count();

        // Derived table: contagem de tentativas por contato (uma passagem no log)
        $tentativasSub = DB::table('log_preditiva')
            ->select('contato_id', DB::raw('COUNT(*) as total'))
            ->where('empresa_id', $empresaId)
            ->groupBy('contato_id');

        // Derived table: max id por contato para obter a última tabulação sem subquery correlacionado
        $maxLogIdSub = DB::table('log_preditiva')
            ->select('contato_id', DB::raw('MAX(id) as max_id'))
            ->where('empresa_id', $empresaId)
            ->groupBy('contato_id');

        $ultimaTabulacaoSub = DB::table('log_preditiva as linner')
            ->joinSub($maxLogIdSub, 'lmax', fn ($j) => $j->on('linner.id', '=', 'lmax.max_id'))
            ->select('linner.contato_id', 'linner.tabulacao');

        $query = DB::table('preditiva as p')
            ->join('contatos as c', 'c.id', '=', 'p.contato_id')
            ->leftJoinSub($tentativasSub, 'tent', fn ($j) => $j->on('tent.contato_id', '=', 'p.contato_id'))
            ->leftJoinSub($ultimaTabulacaoSub, 'ut', fn ($j) => $j->on('ut.contato_id', '=', 'p.contato_id'))
            ->where('p.status', 'Y')
            ->where('p.empresa_id', $empresaId)
            ->select(
                'p.id as preditiva_id',
                'c.nome_cliente',
                'c.valor_plano_atual',
                'c.telefone1',
                'p.contato_id',
                DB::raw('COALESCE(tent.total, 0) as tentativas'),
                'ut.tabulacao as ultima_tabulacao'
            );

        // Filtro por nome do cliente
        if ($request->filled('nome_cliente')) {
            $query->where('c.nome_cliente', 'LIKE', '%'.$request->nome_cliente.'%');
        }

        // Filtro por última tabulação aplicado na query (mais eficiente que filtrar na coleção)
        if ($request->filled('ultima_tabulacao')) {
            $query->where('ut.tabulacao', $request->ultima_tabulacao);
        }

        // Filtro por quantidade de tentativas
        if ($request->filled('tentativas')) {
            $tentativasFiltro = $request->tentativas;
            if ($tentativasFiltro === '5+') {
                $query->where(DB::raw('COALESCE(tent.total, 0)'), '>=', 5);
            } else {
                $query->where(DB::raw('COALESCE(tent.total, 0)'), '=', (int) $tentativasFiltro);
            }
        }

        $leads = $query->get();

        return response()->json([
            'total_leads_fila' => $totalLeadsFila,
            'tentativas_hoje' => $tentativasHoje,
            'conversoes_hoje' => $conversoesHoje,
            'recusas_hoje' => $recusasHoje,
            'leads' => $leads,
        ]);
    }

    public function leadDescartados()
    {
        return view('content.pages.mailing.leads-descartados');
    }

    public function getLeadsDescartados()
    {
        $empresaId = Auth::user()->empresa_id;
        $leadsDescartados = Contatos::select(['id', 'nome_cliente', 'cpf', 'telefone1', 'valor_plano_atual', 'categoria', 'created_at', 'nome_base', 'updated_at'])
            ->where('empresa_id', $empresaId)
            ->where('status', 'N')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json(['data' => $leadsDescartados]);
    }

    public function getComentariosLead($contatoId)
    {
        $comentarios = DB::table('comentarios')
            ->leftJoin('users', 'comentarios.user_id', '=', 'users.id')
            ->where('comentarios.contato_id', $contatoId)
            ->where('comentarios.empresa_id', Auth::user()->empresa_id)
            ->select('comentarios.*', 'users.name as autor')
            ->orderBy('comentarios.created_at', 'desc')
            ->get();

        return response()->json($comentarios);
    }

    /**
     * Envia um lead descartado para a fila preditiva
     */
    public function sendDiscardedLeadToPreditiva(Request $request)
    {
        try {
            $contatoId = $request->id;
            $empresaId = Auth::user()->empresa_id;

            // Verificar se o contato pertence a empresa e esta descartado
            $contato = Contatos::where('id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->where('status', 'N')
                ->first();

            if (! $contato) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead nao encontrado ou nao esta descartado.',
                ], 404);
            }

            // Verificar duplicidade - se ja existe na preditiva com status Y
            $existsInPreditiva = Preditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->where('status', 'Y')
                ->exists();

            if ($existsInPreditiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead ja esta na fila preditiva.',
                ], 422);
            }

            DB::beginTransaction();

            // 1. Reativar o contato
            $contato->status = 'Y';
            $contato->updated_at = now();
            $contato->save();

            // 2. Remover registro antigo da preditiva se existir (status N)
            Preditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->delete();

            // 3. Criar novo registro na preditiva
            Preditiva::create([
                'empresa_id' => $empresaId,
                'contato_id' => $contatoId,
                'status' => 'Y',
            ]);

            // 4. Limpar logs anteriores para comecar do zero
            LogPreditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead enviado para preditiva com sucesso.',
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar lead: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Envia multiplos leads descartados para a fila preditiva (com chunking para performance)
     */
    public function sendMultipleDiscardedLeadsToPreditiva(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            $empresaId = Auth::user()->empresa_id;

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum lead selecionado.',
                ], 400);
            }

            $totalCount = count($ids);
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $chunkSize = 50;

            // Processar em chunks para evitar timeout
            $chunks = array_chunk($ids, $chunkSize);

            foreach ($chunks as $chunk) {
                DB::beginTransaction();
                try {
                    foreach ($chunk as $contatoId) {
                        // Verificar propriedade e status
                        $contato = Contatos::where('id', $contatoId)
                            ->where('empresa_id', $empresaId)
                            ->where('status', 'N')
                            ->first();

                        if (! $contato) {
                            $errorCount++;

                            continue;
                        }

                        // Verificar duplicidade
                        $existsInPreditiva = Preditiva::where('contato_id', $contatoId)
                            ->where('empresa_id', $empresaId)
                            ->where('status', 'Y')
                            ->exists();

                        if ($existsInPreditiva) {
                            $skippedCount++;

                            continue;
                        }

                        // Reativar contato
                        $contato->status = 'Y';
                        $contato->updated_at = now();
                        $contato->save();

                        // Remover registro antigo da preditiva se existir
                        Preditiva::where('contato_id', $contatoId)
                            ->where('empresa_id', $empresaId)
                            ->delete();

                        // Criar novo registro na preditiva
                        Preditiva::create([
                            'empresa_id' => $empresaId,
                            'contato_id' => $contatoId,
                            'status' => 'Y',
                        ]);

                        // Limpar logs anteriores
                        LogPreditiva::where('contato_id', $contatoId)
                            ->where('empresa_id', $empresaId)
                            ->delete();

                        $successCount++;
                    }
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $errorCount += count($chunk);
                }
            }

            // Construir mensagem de resposta
            $message = "{$successCount} lead(s) enviado(s) com sucesso para a fila preditiva.";

            if ($skippedCount > 0) {
                $message .= " {$skippedCount} ja estavam na fila.";
            }

            if ($errorCount > 0) {
                $message .= " {$errorCount} com falha.";
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => $message,
                'stats' => [
                    'total' => $totalCount,
                    'success' => $successCount,
                    'skipped' => $skippedCount,
                    'errors' => $errorCount,
                ],
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar: '.$th->getMessage(),
            ], 500);
        }
    }

    public function desativarLeadPreditiva($contatoId)
    {
        try {
            $empresaId = Auth::user()->empresa_id;

            DB::beginTransaction();

            // Remove da preditiva
            Preditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->update(['status' => 'N']);

            // Registra no log
            LogPreditiva::create([
                'empresa_id' => $empresaId,
                'user_id' => Auth::id(),
                'contato_id' => $contatoId,
                'tabulacao' => 'DESATIVADO',
                'acao' => 'DESCARTE',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead desativado da preditiva com sucesso.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao desativar lead: '.$th->getMessage(),
            ], 500);
        }
    }

    public function excluirLeadPreditiva($contatoId)
    {
        try {
            $empresaId = Auth::user()->empresa_id;

            // Verifica se existe venda cadastrada
            $searchForLaunchedSale = $this->vendasRepository->checkExistenceSale($contatoId);

            if ($searchForLaunchedSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esse Lead possui venda cadastrada, exclusão cancelada.',
                ], 422);
            }

            DB::beginTransaction();

            // Desativa o contato
            Contatos::where('id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->update(['status' => 'N']);

            // Remove da preditiva
            Preditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->delete();

            // Registra no log
            LogPreditiva::create([
                'empresa_id' => $empresaId,
                'user_id' => Auth::id(),
                'contato_id' => $contatoId,
                'tabulacao' => 'EXCLUIDO',
                'acao' => 'DESCARTE',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead excluído permanentemente com sucesso.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir lead: '.$th->getMessage(),
            ], 500);
        }
    }

    public function removerDaPreditiva($contatoId)
    {
        try {
            $empresaId = Auth::user()->empresa_id;

            DB::beginTransaction();

            // Remove apenas da preditiva, mantém o contato ativo
            Preditiva::where('contato_id', $contatoId)
                ->where('empresa_id', $empresaId)
                ->delete();

            // Registra no log
            LogPreditiva::create([
                'empresa_id' => $empresaId,
                'user_id' => Auth::id(),
                'contato_id' => $contatoId,
                'tabulacao' => 'REMOVIDO DA FILA',
                'acao' => 'DESCARTE',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lead removido da fila preditiva com sucesso.',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover lead: '.$th->getMessage(),
            ], 500);
        }
    }

    public function getTabulacoesDistintas()
    {
        try {
            $empresaId = Auth::user()->empresa_id;

            $tabulacoes = DB::table('log_preditiva')
                ->where('empresa_id', $empresaId)
                ->whereNotNull('tabulacao')
                ->where('tabulacao', '!=', '')
                ->distinct()
                ->orderBy('tabulacao')
                ->pluck('tabulacao')
                ->values();

            return response()->json([
                'success' => true,
                'tabulacoes' => $tabulacoes,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tabulações: '.$th->getMessage(),
            ], 500);
        }
    }

    public function limparLogsPreditiva(Request $request)
    {
        $ids = $request->input('ids', []);
        $empresaId = Auth::user()->empresa_id;

        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Nenhum lead selecionado.'], 400);
        }

        try {
            $deleted = LogPreditiva::whereIn('contato_id', $ids)
                ->where('empresa_id', $empresaId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Logs de {$deleted} registros limpos com sucesso. As tentativas foram reiniciadas.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao limpar logs.'], 500);
        }
    }

    public function importarParaPreditiva(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,csv',
                'base' => 'required|string',
            ]);

            $empresaId = Auth::user()->empresa_id;
            $ignorarDuplicados = $request->input('ignorar_duplicados', false);

            // Gerar ID único para a operação (usado como id_operacao)
            $uniqueIdBase = uniqid('preditiva_', true);

            // Primeira passada: ler o arquivo e extrair os CPFs válidos
            // Usar ContatosImport temporariamente para ler com cabeçalho
            $rows = Excel::toArray(new ContatosImportPreditiva($request->base, $uniqueIdBase, []), $request->file('file'));
            $cpfsParaValidar = [];

            foreach ($rows[0] as $row) {
                // CPF vem pela chave 'cpf' devido ao WithHeadingRow
                if (! empty($row['cpf'])) {
                    $cpfLimpo = Helpers::cleanSpecialCharacters($row['cpf']);
                    // Aceitar qualquer CPF não vazio após limpeza
                    if (! empty($cpfLimpo)) {
                        $cpfsParaValidar[] = $cpfLimpo;
                    }
                }
            }

            // Buscar CPFs que já existem na base
            $cpfsExistentes = [];
            if (count($cpfsParaValidar) > 0) {
                $cpfsExistentes = Contatos::where('empresa_id', $empresaId)
                    ->whereIn('cpf', $cpfsParaValidar)
                    ->pluck('cpf')
                    ->toArray();
            }

            // Se houver duplicados e o usuário não confirmou ignorá-los
            if (count($cpfsExistentes) > 0 && ! $ignorarDuplicados) {
                $totalLeads = count($cpfsParaValidar);
                $totalDuplicados = count($cpfsExistentes);
                $totalNaoDuplicados = $totalLeads - $totalDuplicados;

                return response()->json([
                    'error' => true,
                    'duplicados_encontrados' => true,
                    'message' => $totalDuplicados.' CPF(s) duplicado(s) encontrado(s). '.$totalNaoDuplicados.' lead(s) podem ser importados.',
                    'cpfs' => $cpfsExistentes,
                    'total_leads' => $totalLeads,
                    'total_duplicados' => $totalDuplicados,
                    'total_nao_duplicados' => $totalNaoDuplicados,
                ], 422);
            }

            // Segunda passada: importar os dados (ignorando duplicados se solicitado)
            Excel::import(
                new ContatosImportPreditiva($request->base, $uniqueIdBase, $cpfsExistentes),
                $request->file('file')
            );

            $totalImportados = count($cpfsParaValidar) - count($cpfsExistentes);
            $mensagem = $totalImportados.' lead(s) importado(s) para a preditiva com sucesso.';

            if (count($cpfsExistentes) > 0) {
                $mensagem .= ' '.count($cpfsExistentes).' CPF(s) duplicado(s) foram ignorados.';
            }

            return response()->json([
                'error' => false,
                'message' => $mensagem,
                'total_importados' => $totalImportados,
                'total_ignorados' => count($cpfsExistentes),
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Erro ao importar leads: '.$th->getMessage(),
            ], 500);
        }
    }

    public function indexImportarPreditiva()
    {
        return view('content.pages.mailing.importarPreditiva');
    }

    /**
     * Lista todas as regras de priorização da empresa
     */
    public function regrasIndex()
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $regras = $this->preditivaRegraRepository->getRegrasByEmpresa($empresaId);

            return response()->json([
                'success' => true,
                'regras' => $regras,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar regras: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Cria uma nova regra de priorização
     */
    public function regrasStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nome' => 'required|string|max:100',
                'campo' => 'required|string|max:50',
                'operador' => 'required|in:=,!=,>,>=,<,<=,LIKE,IN',
                'valor' => 'required|string',
                'peso' => 'required|integer|min:1|max:100',
                'descricao' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $campo = $request->campo;
            if (! array_key_exists($campo, PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo não permitido para priorização.',
                ], 422);
            }

            $campoConfig = PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS[$campo];
            if (! in_array($request->operador, $campoConfig['operadores'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador não permitido para este campo.',
                ], 422);
            }

            $empresaId = Auth::user()->empresa_id;

            $id = $this->preditivaRegraRepository->create([
                'empresa_id' => $empresaId,
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'campo' => $request->campo,
                'operador' => $request->operador,
                'valor' => $request->valor,
                'peso' => $request->peso,
                'ativo' => 'Y',
            ]);

            if (! $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar regra.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Regra criada com sucesso.',
                'id' => $id,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar regra: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualiza uma regra de priorização existente
     */
    public function regrasUpdate(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nome' => 'required|string|max:100',
                'campo' => 'required|string|max:50',
                'operador' => 'required|in:=,!=,>,>=,<,<=,LIKE,IN',
                'valor' => 'required|string',
                'peso' => 'required|integer|min:1|max:100',
                'descricao' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $regra = $this->preditivaRegraRepository->findById($id);
            if (! $regra || $regra->empresa_id !== Auth::user()->empresa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Regra não encontrada.',
                ], 404);
            }

            $campo = $request->campo;
            if (! array_key_exists($campo, PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo não permitido para priorização.',
                ], 422);
            }

            $campoConfig = PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS[$campo];
            if (! in_array($request->operador, $campoConfig['operadores'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Operador não permitido para este campo.',
                ], 422);
            }

            $updated = $this->preditivaRegraRepository->update($id, [
                'nome' => $request->nome,
                'descricao' => $request->descricao,
                'campo' => $request->campo,
                'operador' => $request->operador,
                'valor' => $request->valor,
                'peso' => $request->peso,
            ]);

            if (! $updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar regra.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Regra atualizada com sucesso.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar regra: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Exclui uma regra de priorização
     */
    public function regrasDestroy($id)
    {
        try {
            $regra = $this->preditivaRegraRepository->findById($id);
            if (! $regra || $regra->empresa_id !== Auth::user()->empresa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Regra não encontrada.',
                ], 404);
            }

            $deleted = $this->preditivaRegraRepository->delete($id);

            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao excluir regra.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Regra excluída com sucesso.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir regra: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Alterna o status ativo/inativo de uma regra
     */
    public function regrasToggle($id)
    {
        try {
            $regra = $this->preditivaRegraRepository->findById($id);
            if (! $regra || $regra->empresa_id !== Auth::user()->empresa_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Regra não encontrada.',
                ], 404);
            }

            $toggled = $this->preditivaRegraRepository->toggleAtivo($id);

            if (! $toggled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao alterar status da regra.',
                ], 500);
            }

            $regraAtualizada = $this->preditivaRegraRepository->findById($id);

            return response()->json([
                'success' => true,
                'message' => 'Status da regra alterado com sucesso.',
                'ativo' => $regraAtualizada->ativo,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Reordena as regras de priorização
     */
    public function regrasReordenar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ordens' => 'required|array',
                'ordens.*' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $empresaId = Auth::user()->empresa_id;
            $ordens = $request->ordens;

            foreach ($ordens as $id) {
                $regra = $this->preditivaRegraRepository->findById($id);
                if (! $regra || $regra->empresa_id !== $empresaId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Regra não autorizada na lista.',
                    ], 403);
                }
            }

            $reordered = $this->preditivaRegraRepository->reordenar($ordens);

            if (! $reordered) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao reordenar regras.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Regras reordenadas com sucesso.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao reordenar: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna os campos disponíveis para priorização com seus operadores
     */
    public function getCamposDisponiveis()
    {
        try {
            $campos = [];

            foreach (PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS as $campo => $config) {
                $campos[] = [
                    'campo' => $campo,
                    'label' => $config['label'],
                    'tipo' => $config['tipo'],
                    'operadores' => $config['operadores'],
                    'valores' => $config['valores'] ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'campos' => $campos,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar campos: '.$th->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna os valores únicos de um campo específico da tabela contatos
     */
    public function getValoresUnicos($campo)
    {
        try {
            if (! array_key_exists($campo, PreditivaRegraPriorizacao::CAMPOS_PERMITIDOS)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo não permitido.',
                ], 422);
            }

            $empresaId = Auth::user()->empresa_id;

            $valores = DB::table('contatos')
                ->where('empresa_id', $empresaId)
                ->whereNotNull($campo)
                ->where($campo, '!=', '')
                ->distinct()
                ->orderBy($campo)
                ->pluck($campo)
                ->values();

            return response()->json([
                'success' => true,
                'valores' => $valores,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar valores: '.$th->getMessage(),
            ], 500);
        }
    }

    // ============================================================
    // Configurações da Preditiva
    // ============================================================

    public function getConfiguracaoPreditiva()
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $config = PreditivaConfiguracao::getOrDefault($empresaId);
            $tabsHard = PreditivaTabulacaoHard::listForEmpresa($empresaId);

            return response()->json([
                'success' => true,
                'config' => $config,
                'tabs_hard' => $tabsHard,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function salvarConfiguracaoPreditiva(Request $request)
    {
        try {
            $empresaId = Auth::user()->empresa_id;

            $request->validate([
                'cooldown_horas' => 'required|integer|min:0|max:168',
                'exclusao_usuario_dias' => 'nullable|integer|min:1|max:3650',
                'limite_descartes_soft' => 'required|integer|min:1|max:50',
                'limite_descartes_hard' => 'required|integer|min:1|max:10',
            ]);

            PreditivaConfiguracao::updateOrCreate(
                ['empresa_id' => $empresaId],
                [
                    'cooldown_horas' => $request->cooldown_horas,
                    'exclusao_usuario_dias' => $request->exclusao_usuario_dias,
                    'limite_descartes_soft' => $request->limite_descartes_soft,
                    'limite_descartes_hard' => $request->limite_descartes_hard,
                ]
            );

            return response()->json(['success' => true, 'message' => 'Configuracoes salvas com sucesso.']);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function storeTabulacaoHard(Request $request)
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $tabulacao = strtoupper(trim($request->tabulacao ?? ''));

            if (empty($tabulacao)) {
                return response()->json(['success' => false, 'message' => 'Tabulacao nao pode ser vazia.'], 422);
            }

            $tab = PreditivaTabulacaoHard::firstOrCreate([
                'empresa_id' => $empresaId,
                'tabulacao' => $tabulacao,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tabulacao HARD adicionada.',
                'id' => $tab->id,
                'tabulacao' => $tab->tabulacao,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function destroyTabulacaoHard(int $id)
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $deleted = PreditivaTabulacaoHard::where('id', $id)
                ->where('empresa_id', $empresaId)
                ->delete();

            if (! $deleted) {
                return response()->json(['success' => false, 'message' => 'Tabulacao nao encontrada.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Tabulacao removida.']);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }
}
