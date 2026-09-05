<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\TabulationCode;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Events\ContratoImplantado;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Jobs\GerarRecebiveisJob;
use App\Mail\BoasVindasMail;
use App\Models\AcessoEmpresa;
use App\Models\DocumentoDiretorio;
use App\Models\Empresa;
use App\Models\Faq;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\PosVendaAnotacao;
use App\Models\PosVendaDemandaTemplate;
use App\Models\Recebivel;
use App\Models\RegrasComissionamento;
use App\Models\Tabulacoes;
use App\Models\User;
use App\Models\VendaDemanda;
use App\Models\VendaDependente;
use App\Models\VendaHistorico;
use App\Models\VendaPortabilidade;
use App\Models\Vendas;
use App\Models\VendaTitular;
use App\Notifications\StatusPropostaAlterada;
use App\Notifications\VendaEstornadaComComissaoPaga;
use App\Notifications\VendaRetomadaPeloBackoffice;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\VendasRepository;
use App\Services\Documentos\NomeDocumentoService;
use App\Services\PosVendaDemandaService;
use App\Services\TabulationCatalog;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class Backoffice extends Controller
{
    protected VendasRepository $vendasRepository;

    protected TabulacoesRepository $tabulacoesRepository;

    protected ContatosCorretoresRepository $contatosCorretoresRepository;

    protected TabulationCatalog $tabulationCatalog;

    public function __construct(

        VendasRepositoryInterface $vendasRepositoryInterface,
        TabulacoesRepositoryInterface $tabulacoesRepositoryInterface,
        ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface,
        TabulationCatalog $tabulationCatalog

    ) {
        $this->vendasRepository = $vendasRepositoryInterface;
        $this->tabulacoesRepository = $tabulacoesRepositoryInterface;
        $this->contatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
        $this->tabulationCatalog = $tabulationCatalog;
    }

    private function canEditContract(Vendas $sale): bool
    {
        $roleId = Auth::user()->user_role_id;

        // ADM e DEVELOPER editam qualquer contrato
        if (in_array($roleId, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])) {
            return true;
        }

        // BACKOFFICE edita qualquer contrato, mesmo sob custódia de outro:
        // o pós-venda precisa anexar documentos, atualizar cancelamentos/
        // portabilidades etc. A única trava por dono é a alteração de status
        // do contrato (ver canAlterStatus).
        if ($roleId === UserRole::BACKOFFICE) {
            return true;
        }

        // SUPERVISOR e demais: somente leitura
        return false;
    }

    private function internalError(Throwable $exception, string $message): JsonResponse
    {
        report($exception);

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 500);
    }

    private function tabulationId(string $code): int
    {
        return $this->tabulationCatalog->id((int) $this->tenantId(), $code);
    }

    private function vendaDoTenant(int $id): Vendas
    {
        return Vendas::where('empresa_id', $this->tenantId())->findOrFail($id);
    }

    private function operadoraDaVenda(Vendas $venda): ?Operadora
    {
        $query = Operadora::where('empresa_id', $venda->empresa_id);
        $operadora = $venda->operadora_id
            ? (clone $query)->whereKey($venda->operadora_id)->first()
            : null;

        // Compatibilidade com contratos antigos sem operadora_id: o nome serve
        // apenas para localizar o cadastro do mesmo tenant, não para definir regra.
        $operadora ??= $query
            ->whereRaw('UPPER(nome) = ?', [mb_strtoupper(trim((string) $venda->operadora), 'UTF-8')])
            ->first();

        return $operadora;
    }

    private function valoresCoparticipacaoDaVenda(Vendas $venda): array
    {
        return $this->operadoraDaVenda($venda)?->valoresCoparticipacao() ?? ['Y', 'N'];
    }

    private function titularDoTenant(int $id): VendaTitular
    {
        return VendaTitular::whereHas('venda', fn ($query) => $query
            ->where('empresa_id', $this->tenantId()))->findOrFail($id);
    }

    private function dependenteDoTenant(int $id): VendaDependente
    {
        return VendaDependente::whereHas('venda', fn ($query) => $query
            ->where('empresa_id', $this->tenantId()))->findOrFail($id);
    }

    private function portabilidadeDoTenant(int $id): VendaPortabilidade
    {
        return VendaPortabilidade::whereHas('venda', fn ($query) => $query
            ->where('empresa_id', $this->tenantId()))->findOrFail($id);
    }

    private function destinosRetomadaEstorno(): array
    {
        return array_values($this->tabulationCatalog->requiredIds(
            (int) $this->tenantId(),
            [
                TabulationCode::VENDA,
                TabulationCode::ANALISE_DOCUMENTOS,
                TabulationCode::AGUARDANDO_ASSINATURA_DS,
                TabulationCode::ANALISE_OPERADORA,
                TabulationCode::CONTRATO_GERADO_AGUARDANDO_ASSINATURA,
                TabulationCode::BOLETO_DISPONIVEL,
                TabulationCode::REGULARIZADO,
            ]
        ));
    }

    private function canAlterStatus(Vendas $sale): bool
    {
        $roleId = Auth::user()->user_role_id;

        if (in_array($roleId, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])) {
            return true;
        }

        // Status do contrato só muda pelo backoffice responsável (custódia).
        if ($roleId === UserRole::BACKOFFICE) {
            return $sale->backoffice_id !== null && $sale->backoffice_id === Auth::id();
        }

        return false;
    }

    private function canReassignContract(): bool
    {
        $roleId = Auth::user()->user_role_id;

        return in_array($roleId, [UserRole::DEVELOPER, UserRole::ADMINISTRATIVO]);
    }

    public function index()
    {
        $empresaId = $this->tenantId();
        $tabulations = $this->tabulacoesRepository->getTabulationsBackoffice($empresaId);
        $isBackoffice = Auth::user()->user_role_id == UserRole::BACKOFFICE;

        // Check-list de pós-venda que o backoffice escolhe no modal de implantação.
        PosVendaDemandaTemplate::seedDefaults($empresaId);
        $posVendaTemplates = PosVendaDemandaTemplate::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get(['id', 'tipo', 'titulo', 'gerar_automatico']);

        return view('content.pages.backoffice.index', [
            'tabulacoes' => $tabulations,
            'isBackoffice' => $isBackoffice,
            'posVendaTemplates' => $posVendaTemplates,
        ]);
    }

    public function listContract()
    {
        $sales = $this->vendasRepository->all($this->tenantId());

        return response()->json([
            'data' => $sales,
        ]);
    }

    public function listSalesFilter(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (! $startDate && ! $endDate) {
            $vendas = $this->vendasRepository->all($this->tenantId());
        } else {
            $vendas = $this->vendasRepository->getSalesFilter($startDate, $endDate, $this->tenantId());
        }

        return response()->json([
            'data' => $vendas,
        ]);
    }

    public function openContract(string $idContract)
    {
        $empresaId = (int) $this->tenantId();
        $sale = $this->vendasRepository->find($idContract);

        if (! $sale) {
            abort(404, 'Contrato não encontrado.');
        }

        if ($sale->empresa_id !== $this->tenantId()) {
            abort(404, 'Contrato não encontrado.');
        }

        // Vendedor não acessa a tela do backoffice. Se a venda é dele e está em ESTORNO,
        // redireciona para a tela de correção; caso contrário, bloqueia.
        if (Auth::user()->user_role_id === UserRole::VENDEDOR) {
            $tabulacaoAtual = (int) $sale->tabulacao_id;
            if ($sale->user_id === Auth::id() && $tabulacaoAtual === $this->tabulationId(TabulationCode::ESTORNO)) {
                return redirect()->route('sale.editEstorno', $sale->id);
            }
            abort(403, 'Você não tem permissão para acessar este contrato.');
        }

        abort_unless(
            in_array((int) Auth::user()->user_role_id, [
                UserRole::ADMINISTRATIVO,
                UserRole::BACKOFFICE,
                UserRole::SUPERVISOR,
                UserRole::DEVELOPER,
            ], true),
            403,
            'Você não tem permissão para acessar este contrato.'
        );

        $operadoras = Operadora::where('empresa_id', $this->tenantId())->get();

        $selectedOperadora = $operadoras->firstWhere('id', $sale->operadora_id);
        $selectedOperadora ??= $operadoras->first(function ($op) use ($sale) {
            return mb_strtoupper($op->nome, 'UTF-8') === mb_strtoupper($sale->operadora ?? '', 'UTF-8');
        });
        $selectedOperadoraId = optional($selectedOperadora)->id;

        $planosDaOperadora = $selectedOperadoraId
          ? Plano::where('empresa_id', $this->tenantId())
              ->where('operadora_id', $selectedOperadoraId)
              ->get()
          : collect();

        $planosPortabilidade = Plano::where('empresa_id', $this->tenantId())
            ->where('status', 'Y')
            ->orderBy('nome')
            ->get(['id', 'operadora_id', 'nome']);

        $plano = $sale->plano_id
          ? Plano::where('empresa_id', $this->tenantId())->find($sale->plano_id)
          : null;

        $titulares = VendaTitular::with(['plano', 'dependentes', 'operadoraAnterior'])
            ->where('venda_id', $sale->id)
            ->get();

        // Quem disparou as boas-vindas — o selo no cabeçalho mostra data e autor.
        $sale->loadMissing([
            'usuarioBoasVindas' => fn ($query) => $query->tenantActor($empresaId),
        ]);

        // Backoffice responsável
        $isBackofficeRole = Auth::user()->user_role_id == UserRole::BACKOFFICE;
        $hasBackoffice = $sale->backoffice_id !== null;
        $isOwner = $sale->backoffice_id === Auth::id();
        $canEdit = $this->canEditContract($sale);
        $canReassign = $this->canReassignContract();
        $showAssumirDialog = ! $hasBackoffice && $isBackofficeRole;
        $backofficeUser = $hasBackoffice
          ? User::query()->tenantMember($this->tenantId())->find($sale->backoffice_id)
          : null;
        $backofficeUsers = $canReassign
          ? User::query()->tenantMember($this->tenantId())
              ->whereIn('user_role_id', [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])
              ->where('ativo', 'Y')
              ->orderBy('name')
              ->get()
          : collect();

        $backofficeVars = compact(
            'isBackofficeRole', 'hasBackoffice', 'isOwner',
            'canEdit', 'canReassign', 'showAssumirDialog',
            'backofficeUser', 'backofficeUsers'
        );

        // Todos os contratos abrem no layout novo (abas), independente do
        // layout_venda de origem (ANTIGO/IMPORTACAO_SYS): os campos que o layout
        // antigo não tinha aparecem em branco para preenchimento. O layout_venda
        // segue intocado no banco — outros fluxos (recebíveis de importação)
        // dependem dele.
        $dependentes = VendaDependente::with(['plano', 'operadoraAnterior'])
            ->where('venda_id', $sale->id)
            ->get();

        $portabilidades = VendaPortabilidade::with(['operadoraAnterior', 'operadoraDestino', 'planoDestino'])
            ->where('venda_id', $sale->id)
            ->orderBy('sequencial')
            ->get();

        return view('content.pages.backoffice.openContractPME', array_merge([
            'contract' => $sale,
            'operadoras' => $operadoras,
            'selectedOperadoraId' => $selectedOperadoraId,
            'planosDaOperadora' => $planosDaOperadora,
            'planosPortabilidade' => $planosPortabilidade,
            'plano' => $plano,
            'titulares' => $titulares,
            'dependentes' => $dependentes,
            'portabilidades' => $portabilidades,
        ], $backofficeVars));
    }

    public function updateSale(Request $request)
    {
        $sale = $this->vendasRepository->find($request->id);

        if (! $sale || (int) $sale->empresa_id !== (int) $this->tenantId()) {
            abort(404, 'Contrato não encontrado.');
        }

        if (! $this->canEditContract($sale)) {
            return redirect()->back()->with('status', 'error')
                ->with('message', 'Voce nao tem permissao para editar este contrato.');
        }

        if ($this->vendasRepository->updateContract($request->all())) {
            return redirect()->back()->with('status', 'success')->with('message', 'Contrato Atualizado');
        } else {
            return redirect()->back()->with('status', 'error')->with('message', 'Erro ao atualizar contrato ,contate nosso suporte');
        }
    }

    public function alterStatusContract(Request $request)
    {
        $request->validate([
            'idSale' => ['required', 'integer'],
            'tabulacao_id' => [
                'required',
                'integer',
                Rule::exists('tabulacoes', 'id')
                    ->where('empresa_id', $this->tenantId())
                    ->where('tipo_tabulacao', 'A'),
            ],
        ]);

        $sale = $this->vendasRepository->find($request->idSale);

        if (! $sale || (int) $sale->empresa_id !== (int) $this->tenantId()) {
            abort(404, 'Contrato não encontrado.');
        }

        if (! $this->canAlterStatus($sale)) {
            return redirect()->route('backoffice.index')
                ->with('status', 'error')
                ->with('message', 'Somente o backoffice responsavel pelo contrato pode alterar o status.');
        }

        // ESTORNO exige motivo registrado em vendas_historico — devolve ao vendedor.
        // Validado fora do try para que ValidationException flua para o handler do Laravel.
        if ((int) $request->tabulacao_id === $this->tabulationId(TabulationCode::ESTORNO)) {
            $request->validate([
                'motivo_pendencia' => 'required|string|min:10|max:500',
            ], [
                'motivo_pendencia.required' => 'Informe o motivo do estorno (mínimo 10 caracteres).',
                'motivo_pendencia.min' => 'O motivo do estorno deve ter ao menos 10 caracteres.',
            ]);
        }

        try {

            // Status é da VENDA, não do contato — um contato pode ter várias
            // vendas em estágios diferentes (ex.: contrato antigo implantado +
            // nova proposta em andamento).
            $updateContract = $this->vendasRepository->alterStatusVenda(
                $sale->id,
                (int) $request->tabulacao_id,
                $request->observacao_estorno ?? null,
                $request->motivo_pendencia ?? null
            );

            if ((int) $request->tabulacao_id === $this->tabulationId(TabulationCode::IMPLANTADO)) {
                $request->validate([
                    'comprovante' => 'required|file|mimes:jpeg,jpg,png,pdf',
                    'data_implantacao' => 'required|date',
                ]);

                $file = $request->file('comprovante');
                $directory = 'comprovantes/'.$sale->empresa_id.'/'.$sale->id;
                $fileName = 'comprovante_pagamento.'.$file->getClientOriginalExtension();
                Storage::putFileAs($directory, $file, $fileName);

                $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, $request->data_implantacao, $request->motivo_pendencia ?? null, null, $request->numero_proposta);
                dispatch(new GerarRecebiveisJob($sale->id));

                // Gera o check-list de demandas de pós-venda escolhido pelo backoffice
                // no modal de implantação (idempotente). O hidden `demandas_enviadas`
                // distingue "nenhuma marcada" (array vazio) de "modal antigo" (null/auto).
                $demandasSelecionadas = $request->boolean('demandas_enviadas')
                    ? (array) $request->input('demandas', [])
                    : null;
                app(PosVendaDemandaService::class)->gerarParaVenda($sale, $demandasSelecionadas, Auth::id());

                // Salvar acesso da empresa (se preenchido - campos opcionais)
                if ($request->filled('acesso_email') && $request->filled('acesso_senha')) {
                    AcessoEmpresa::create([
                        'venda_id' => $sale->id,
                        'email' => $request->acesso_email,
                        'senha' => $request->acesso_senha,
                        'cpf' => $request->acesso_cpf,
                    ]);
                }

                // Dispara evento de broadcast para usuários administrativos
                event(new ContratoImplantado(
                    contratoId: $sale->id,
                    nomeContrato: $sale->nome_contrato,
                    numeroProposta: $request->numero_proposta ?? $sale->numero_proposta ?? 'N/A',
                    operadora: $sale->operadora ?? 'N/A',
                    empresaId: $sale->empresa_id,
                    alteradoPorNome: Auth::user()->name ?? 'Sistema'
                ));
            }

            if ((int) $request->tabulacao_id === $this->tabulationId(TabulationCode::BOLETO_DISPONIVEL)) {
                $request->validate([
                    'boleto_disponivel' => 'required|file|mimes:jpeg,jpg,png,pdf',
                ]);

                $file = $request->file('boleto_disponivel');
                $directory = 'boleto_disponiveis/'.$sale->empresa_id.'/'.$sale->id;
                $fileName = '/boleto.'.$file->getClientOriginalExtension();
                Storage::putFileAs($directory, $file, $fileName);
                $updateContract = $this->vendasRepository->saveTicket($sale->id, $directory.$fileName);
            }

            if (
                (int) $request->tabulacao_id !== $this->tabulationId(TabulationCode::IMPLANTADO)
                && (int) $request->tabulacao_id !== $this->tabulationId(TabulationCode::BOLETO_DISPONIVEL)
            ) {
                $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, null, $request->motivo_pendencia ?? null, null, $request->numero_proposta);
            }

            if ($updateContract) {
                $tabulation = Tabulacoes::query()
                    ->where('empresa_id', $this->tenantId())
                    ->findOrFail($request->tabulacao_id);
                $vendedor = User::query()
                    ->tenantMember($this->tenantId())
                    ->findOrFail($sale->user_id);
                $vendedor->notify(new StatusPropostaAlterada(
                    vendaId: $sale->id,
                    novoStatus: $tabulation->descricao,
                    alteradoPorId: Auth::id(),
                    alteradoPorNome: Auth::user()->name ?? null,
                    tabulacaoCode: $tabulation->codigo
                ));

                // WhatsApp ao vendedor desativado: o aviso de mudança de status agora
                // é entregue pelo mascote assistente, que consome a notificação
                // StatusPropostaAlterada acima (GET /comercial/avisos-mascote).

                // Alerta financeiro: estorno em venda com comissão paga não é estornado automaticamente.
                if ((int) $request->tabulacao_id === $this->tabulationId(TabulationCode::ESTORNO) && $sale->comissao_paga) {
                    $admins = User::query()->tenantMember((int) $sale->empresa_id)
                        ->where('user_role_id', UserRole::ADMINISTRATIVO)
                        ->where('ativo', 'Y')
                        ->get();

                    if ($admins->isNotEmpty()) {
                        Notification::send($admins, new VendaEstornadaComComissaoPaga(
                            vendaId: $sale->id,
                            nomeContrato: $sale->nome_contrato ?? "#{$sale->id}",
                            vendedorNome: $vendedor->name ?? null,
                            estornadoPorNome: Auth::user()->name ?? null,
                            motivo: $request->motivo_pendencia ?? null,
                        ));
                    }
                }

                return redirect()->route(route: 'backoffice.index')->with('status', 'success')->with('message', 'Contrato Atualizado');
            } else {
                return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', 'Erro ao atualizar contrato ,contate nosso suporte');
            }
        } catch (\Throwable $th) {
            return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', 'Erro ao atualizar contrato ,contate nosso suporte');
        }
    }

    /**
     * Mudança rápida de status via Kanban (sem modal)
     * Apenas para status que não requerem dados adicionais
     */
    public function quickStatusChange(Request $request)
    {
        try {
            $request->validate([
                'venda_id' => 'required|integer',
                'tabulacao_id' => [
                    'required',
                    'integer',
                    Rule::exists('tabulacoes', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('tipo_tabulacao', 'A'),
                ],
            ]);

            $vendaId = $request->venda_id;
            $tabulacaoId = $request->tabulacao_id;

            // Status que requerem modal (não permitidos aqui)
            $statusComModal = array_values($this->tabulationCatalog->requiredIds(
                (int) $this->tenantId(),
                [TabulationCode::IMPLANTADO, TabulationCode::PENDENCIA, TabulationCode::ESTORNO]
            ));

            if (in_array($tabulacaoId, $statusComModal)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este status requer informações adicionais. Use o modal.',
                    'requires_modal' => true,
                ], 400);
            }

            $sale = $this->vendasRepository->find($vendaId);
            if (! $sale || $sale->empresa_id != $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venda não encontrada.',
                ], 404);
            }

            if (! $this->canAlterStatus($sale)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Somente o backoffice responsavel pelo contrato pode alterar o status.',
                ], 403);
            }

            $updateContract = $this->vendasRepository->alterStatusVenda($sale->id, (int) $tabulacaoId);

            if ($updateContract) {
                // Notificar vendedor
                $tabulation = Tabulacoes::query()
                    ->where('empresa_id', $this->tenantId())
                    ->findOrFail($tabulacaoId);
                $vendedor = User::query()
                    ->tenantMember($this->tenantId())
                    ->findOrFail($sale->user_id);
                $vendedor->notify(new StatusPropostaAlterada(
                    vendaId: $sale->id,
                    novoStatus: $tabulation->descricao,
                    alteradoPorId: Auth::id(),
                    alteradoPorNome: Auth::user()->name ?? null,
                    tabulacaoCode: $tabulation->codigo
                ));

                // WhatsApp ao vendedor desativado: o aviso de mudança de status agora
                // é entregue pelo mascote assistente, que consome a notificação
                // StatusPropostaAlterada acima (GET /comercial/avisos-mascote).

                return response()->json([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso!',
                    'novo_status' => $tabulation->descricao,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar status.',
            ], 500);

        } catch (\Throwable $th) {
            return $this->internalError($th, 'Não foi possível atualizar o status neste momento.');
        }
    }

    public function downloadPaymentProof($id)
    {
        $sale = $this->vendasRepository->find($id);

        if (
            ! $sale
            || (int) $sale->empresa_id !== (int) $this->tenantId()
            || (int) $sale->tabulacao_id !== $this->tabulationId(TabulationCode::IMPLANTADO)
        ) {
            abort(404);
        }

        $directory = 'comprovantes/'.$sale->empresa_id.'/'.$sale->id;
        $files = Storage::files($directory);

        if (empty($files)) {
            abort(404);
        }

        return Storage::download($files[0]);
    }

    public function deleteContract($id)
    {
        $sale = $this->vendasRepository->find($id);

        if (! $sale || (int) $sale->empresa_id !== (int) $this->tenantId()) {
            abort(404, 'Contrato não encontrado.');
        }

        if (! $this->canEditContract($sale)) {
            abort(403, 'Você não tem permissão para excluir este contrato.');
        }

        $deleteContract = $this->vendasRepository->delete($id);

        if (request()->wantsJson()) {
            if ($deleteContract) {
                return response()->json(['success' => true, 'message' => 'Contrato deletado com sucesso']);
            }

            return response()->json(['success' => false, 'message' => 'Erro ao deletar contrato, contate nosso suporte'], 500);
        }

        if ($deleteContract) {
            return redirect()->route(route: 'backoffice.index')->with('status', 'success')->with('message', 'Contrato Deletado com sucesso');
        } else {
            return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', 'Erro ao Deletar contrato ,contate nosso suporte');
        }
    }

    // Telas antigas separadas (Cadastrar Operadora / Cadastrar Planos) foram
    // unificadas em "Operadoras e Planos" — redirecionam para a nova.
    public function planos()
    {
        return redirect()->route('backoffice.operadorasPlanos');
    }

    public function operadoras()
    {
        return redirect()->route('backoffice.operadorasPlanos');
    }

    /** Tela única: cadastra a operadora e, na mesma tela, os planos dela. */
    public function operadorasPlanos()
    {
        return view('content.pages.backoffice.operadoras-planos');
    }

    /** Operadoras da empresa com seus planos aninhados (para o master-detail). */
    public function getOperadorasComPlanos()
    {
        $empresaId = $this->tenantId();
        $planosComVenda = $this->planosComVenda($empresaId);

        $planos = Plano::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'operadora_id', 'nome', 'status', 'acomodacao'])
            ->map(fn ($plano) => [
                'id' => $plano->id,
                'operadora_id' => $plano->operadora_id,
                'nome' => $plano->nome,
                'status' => $plano->status,
                'acomodacao' => $plano->acomodacao,
                'can_delete' => ! $planosComVenda->contains($plano->id),
            ])
            ->groupBy('operadora_id');

        $operadorasComVenda = Vendas::where('empresa_id', $empresaId)
            ->whereNotNull('operadora_id')
            ->pluck('operadora_id')
            ->merge(
                Plano::where('empresa_id', $empresaId)
                    ->whereIn('id', $planosComVenda)
                    ->pluck('operadora_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique();

        $operadoras = Operadora::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome', 'diretorio_documentos', 'coparticipacao_formato', 'angariacao_padrao', 'iof_percentual', 'cor_marca', 'logo_path', 'app_ios_url', 'app_android_url', 'status'])
            ->map(fn ($op) => [
                'id' => $op->id,
                'nome' => $op->nome,
                'diretorio_documentos' => $op->diretorio_documentos,
                'coparticipacao_formato' => $op->coparticipacao_formato,
                'angariacao_padrao' => $op->angariacao_padrao,
                'iof_percentual' => $op->iof_percentual,
                'cor_marca' => $op->cor_marca,
                'logo_path' => $op->logo_path,
                'app_ios_url' => $op->app_ios_url,
                'app_android_url' => $op->app_android_url,
                'status' => $op->status,
                'planos' => ($planos[$op->id] ?? collect())->values(),
                'can_delete' => ! $operadorasComVenda->contains($op->id),
            ]);

        return response()->json(['success' => true, 'operadoras' => $operadoras]);
    }

    public function toggleOperadoraStatus($id)
    {
        $op = Operadora::where('id', $id)->where('empresa_id', $this->tenantId())->first();
        if (! $op) {
            return response()->json(['success' => false, 'message' => 'Operadora não encontrada.'], 404);
        }
        $op->update(['status' => $op->status === 'Y' ? 'N' : 'Y']);

        return response()->json(['success' => true, 'status' => $op->status]);
    }

    public function updateOperadoraRegrasComerciais(Request $request, $id)
    {
        $empresaId = (int) $this->tenantId();
        $operadora = Operadora::where('empresa_id', $empresaId)->find($id);
        if (! $operadora) {
            return response()->json(['success' => false, 'message' => 'Operadora não encontrada.'], 404);
        }

        $validated = $request->validate([
            'coparticipacao_formato' => ['required', Rule::in([
                Operadora::COPARTICIPACAO_SIM_NAO,
                Operadora::COPARTICIPACAO_PARCIAL_COMPLETA,
            ])],
            'angariacao_padrao' => ['required', 'boolean'],
            'iof_percentual' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'cor_marca' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value === null || $value === '') {
                    return;
                }

                $urlValida = str_starts_with($value, 'https://') && filter_var($value, FILTER_VALIDATE_URL);
                $caminhoLocalSeguro = str_starts_with($value, 'assets/')
                    && ! str_contains($value, '..')
                    && preg_match('/^assets\/[A-Za-z0-9_.\/-]+$/', $value);

                if (! $urlValida && ! $caminhoLocalSeguro) {
                    $fail('Informe uma URL HTTPS ou um caminho local iniciado por assets/.');
                }
            }],
            'app_ios_url' => $this->regrasUrlHttpsOpcional(),
            'app_android_url' => $this->regrasUrlHttpsOpcional(),
        ]);

        $operadora->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Regras comerciais atualizadas.',
        ]);
    }

    public function updateOperadoraDiretorioDocumentos(Request $request, $id, NomeDocumentoService $nomes)
    {
        $operadora = Operadora::where('id', $id)
            ->where('empresa_id', $this->tenantId())
            ->first();
        if (! $operadora) {
            return response()->json(['success' => false, 'message' => 'Operadora não encontrada.'], 404);
        }

        $request->validate(['diretorio_documentos' => ['required', 'string', 'max:120']]);
        $diretorio = trim((string) $request->input('diretorio_documentos'));
        if ($nomes->segmento($diretorio, '') !== $diretorio) {
            return response()->json(['success' => false, 'message' => 'Informe somente o nome exato da pasta, sem barras ou caracteres especiais.'], 422);
        }

        $raiz = trim(config('documentos.root'), '/');
        $catalogada = DocumentoDiretorio::whereRaw('LOWER(caminho) = LOWER(?)', ["{$raiz}/{$diretorio}"])->first();
        if (! $catalogada) {
            return response()->json([
                'success' => false,
                'message' => "A pasta {$raiz}/{$diretorio} não consta no catálogo sincronizado. Atualize o catálogo antes de vincular.",
            ], 422);
        }

        $diretorio = $catalogada->nome;
        $operadora->update(['diretorio_documentos' => $diretorio]);

        return response()->json([
            'success' => true,
            'message' => 'Pasta de documentos vinculada.',
            'diretorio_documentos' => $diretorio,
        ]);
    }

    public function saudeDocumentos()
    {
        abort_unless(in_array((int) Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE, UserRole::DEVELOPER], true), 403);
        $estados = \App\Models\VendaDocumento::where('empresa_id', $this->tenantId())->whereNull('deleted_at')
            ->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $maisAntigo = \App\Models\VendaDocumento::where('empresa_id', $this->tenantId())
            ->whereIn('status', ['RECEBIDO', 'VERIFICANDO', 'AGUARDANDO_ENVIO', 'ENVIANDO'])
            ->oldest()->value('created_at');

        $filas = ['documentos-transfer' => null];
        try {
            if (config('queue.default') === 'database') {
                foreach (array_keys($filas) as $fila) {
                    $filas[$fila] = DB::table(config('queue.connections.database.table', 'jobs'))->where('queue', $fila)->count();
                }
            } elseif (config('queue.default') === 'redis') {
                $redis = \Illuminate\Support\Facades\Redis::connection(config('queue.connections.redis.connection', 'default'));
                foreach (array_keys($filas) as $fila) {
                    $filas[$fila] = (int) $redis->llen("queues:{$fila}");
                }
            }
        } catch (\Throwable) {
            // O estado dos documentos continua disponível mesmo se o backend da fila estiver indisponível.
        }

        return response()->json([
            'success' => true,
            'estados' => $estados,
            'pendentes' => $estados->only(['RECEBIDO', 'VERIFICANDO', 'AGUARDANDO_ENVIO', 'ENVIANDO'])->sum(),
            'mais_antigo_em' => $maisAntigo,
            'diretorios' => DocumentoDiretorio::orderBy('nome')->pluck('nome'),
            'sincronizado_em' => \Illuminate\Support\Facades\Cache::get('documentos:diretorios:sincronizado_em'),
            'sincronizacao_erro' => (bool) \Illuminate\Support\Facades\Cache::get('documentos:diretorios:erro'),
            'ultimo_sftp_em' => \Illuminate\Support\Facades\Cache::get('documentos:sftp:ultimo_sucesso_em'),
            'filas' => $filas,
        ]);
    }

    public function togglePlanoStatus($id)
    {
        $plano = Plano::where('id', $id)->where('empresa_id', $this->tenantId())->first();
        if (! $plano) {
            return response()->json(['success' => false, 'message' => 'Plano não encontrado.'], 404);
        }
        $plano->update(['status' => $plano->status === 'Y' ? 'N' : 'Y']);

        return response()->json(['success' => true, 'status' => $plano->status]);
    }

    public function destroyOperadora($id)
    {
        $empresaId = $this->tenantId();
        $operadora = Operadora::where('id', $id)->where('empresa_id', $empresaId)->first();
        if (! $operadora) {
            return response()->json(['success' => false, 'message' => 'Operadora não encontrada.'], 404);
        }

        $planoIds = Plano::where('empresa_id', $empresaId)->where('operadora_id', $operadora->id)->pluck('id');
        $possuiVenda = Vendas::where('empresa_id', $empresaId)->where('operadora_id', $operadora->id)->exists()
            || $this->planosComVenda($empresaId)->intersect($planoIds)->isNotEmpty();

        if ($possuiVenda) {
            return response()->json([
                'success' => false,
                'message' => 'Esta operadora não pode ser excluída porque possui vendas cadastradas.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($empresaId, $operadora) {
                Plano::where('empresa_id', $empresaId)->where('operadora_id', $operadora->id)->delete();
                $operadora->delete();
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'A operadora possui outros cadastros vinculados e não pode ser excluída.',
            ], 409);
        }

        return response()->json(['success' => true, 'message' => 'Operadora excluída com sucesso.']);
    }

    public function destroyPlano($id)
    {
        $empresaId = $this->tenantId();
        $plano = Plano::where('id', $id)->where('empresa_id', $empresaId)->first();
        if (! $plano) {
            return response()->json(['success' => false, 'message' => 'Plano não encontrado.'], 404);
        }

        if ($this->planosComVenda($empresaId)->contains($plano->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Este plano não pode ser excluído porque possui vendas cadastradas.',
            ], 409);
        }

        try {
            $plano->delete();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'O plano possui outros cadastros vinculados e não pode ser excluído.',
            ], 409);
        }

        return response()->json(['success' => true, 'message' => 'Plano excluído com sucesso.']);
    }

    private function planosComVenda(int $empresaId)
    {
        $diretos = Vendas::where('empresa_id', $empresaId)->whereNotNull('plano_id')->pluck('plano_id');
        $titulares = DB::table('vendas_titulares')
            ->join('vendas', 'vendas.id', '=', 'vendas_titulares.venda_id')
            ->where('vendas.empresa_id', $empresaId)
            ->whereNotNull('vendas_titulares.plano_id')
            ->pluck('vendas_titulares.plano_id');
        $dependentes = DB::table('vendas_dependentes')
            ->join('vendas', 'vendas.id', '=', 'vendas_dependentes.venda_id')
            ->where('vendas.empresa_id', $empresaId)
            ->whereNotNull('vendas_dependentes.plano_id')
            ->pluck('vendas_dependentes.plano_id');

        return $diretos
            ->merge($titulares)
            ->merge($dependentes)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    public function createOperation(Request $request)
    {
        $empresaId = (int) $this->tenantId();
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Y', 'N'])],
            'coparticipacao_formato' => ['sometimes', Rule::in([
                Operadora::COPARTICIPACAO_SIM_NAO,
                Operadora::COPARTICIPACAO_PARCIAL_COMPLETA,
            ])],
            'angariacao_padrao' => ['sometimes', 'boolean'],
            'iof_percentual' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'cor_marca' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        try {
            Operadora::create([
                'empresa_id' => $empresaId,
                'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                'status' => $validated['status'],
                'coparticipacao_formato' => $validated['coparticipacao_formato'] ?? Operadora::COPARTICIPACAO_SIM_NAO,
                'angariacao_padrao' => $validated['angariacao_padrao'] ?? false,
                'iof_percentual' => $validated['iof_percentual'] ?? 0,
                'cor_marca' => $validated['cor_marca'] ?? '#334155',
            ]);

            return response()->json(['success' => true, 'message' => 'Operadora cadastrada com sucesso!'], 201);

        } catch (\Exception $e) {
            return $this->internalError($e, 'Não foi possível cadastrar a operadora neste momento.');
        }
    }

    public function createPlan(Request $request)
    {
        $empresaId = (int) $this->tenantId();
        $validated = $request->validate([
            'operadora_id' => [
                'required',
                'integer',
                Rule::exists('operadoras', 'id')->where('empresa_id', $empresaId),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Y', 'N'])],
            'acomodacao' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            Plano::create([
                'empresa_id' => $empresaId,
                'operadora_id' => $validated['operadora_id'],
                'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                'status' => $validated['status'],
                'acomodacao' => $validated['acomodacao'] ?? null,
            ]);

            return response()->json(['success' => true, 'message' => 'Plano cadastrado com sucesso!'], 201);
        } catch (\Exception $e) {
            return $this->internalError($e, 'Não foi possível cadastrar o plano neste momento.');
        }
    }

    public function getOperators()
    {
        $operators = Operadora::where('empresa_id', $this->tenantId())->get();

        return response()->json(
            $operators
        );
    }

    public function getPlans()
    {
        $plans = Plano::select(
            'planos.id',
            'operadoras.nome as operadora',
            'planos.status',
            'planos.acomodacao',
            'planos.created_at',
            'planos.nome'
        )
            ->leftJoin('operadoras', function ($join) {
                $join->on('operadoras.id', '=', 'planos.operadora_id')
                    ->on('operadoras.empresa_id', '=', 'planos.empresa_id');
            })
            ->where('planos.empresa_id', $this->tenantId())
            ->orderBy('planos.created_at', 'desc')
            ->get();

        return response()->json($plans);
    }

    public function updateTitular(Request $request, int $id)
    {
        try {
            $titular = $this->titularDoTenant($id);

            $vendaId = (int) $request->input('venda_id');
            $venda = $this->vendaDoTenant($vendaId);

            if ($titular->venda_id !== $venda->id) {
                return back()
                    ->withInput()
                    ->with('status', 'error')
                    ->with('message', 'Titular não pertence a esta venda.');
            }

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return back()
                    ->with('status', 'error')
                    ->with('message', 'Acesso negado para esta venda.');
            }

            $valoresCoparticipacao = $this->valoresCoparticipacaoDaVenda($venda);

            // Validação
            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'nome' => ['required', 'string', 'max:90'],
                'email' => ['nullable', 'email', 'max:90'],
                'telefone' => ['nullable', 'string', 'max:50'],
                'plano_id' => [
                    'required',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('operadora_id', $this->operadoraDaVenda($venda)?->id ?? 0),
                ],
                'coparticipacao' => ['required', Rule::in($valoresCoparticipacao)],
            ]);

            DB::transaction(function () use ($titular, $validated) {
                $titular->update([
                    'nome' => $validated['nome'], // se tiver mutator, fará uppercase
                    'email' => $validated['email'] ?? null,
                    'telefone' => Helpers::cleanSpecialCharacters($validated['telefone'] ?? ''),
                    'plano_id' => (int) $validated['plano_id'],
                    'coparticipacao' => strtoupper($validated['coparticipacao']),
                ]);
            });

            return back()
                ->with('status', 'success')
                ->with('message', 'Titular atualizado com sucesso.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Erro de validação.');
        } catch (\Throwable $e) {
            // \Log::error('Erro ao atualizar titular', ['e' => $e]);
            return back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Falha ao atualizar titular.');
        }
    }

    /**
     * Remove um titular e seus dependentes (cascade)
     */
    public function destroyTitular(int $id)
    {
        try {
            $titular = $this->titularDoTenant($id);
            $venda = $this->vendaDoTenant($titular->venda_id);

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            DB::transaction(function () use ($titular) {
                $titular->dependentes()->delete();
                $titular->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Titular removido com sucesso.',
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível remover o titular neste momento.');
        }
    }

    /**
     * Atualiza um titular no layout PME com todos os campos
     */
    public function updateTitularPME(Request $request, int $id)
    {
        try {
            $titular = $this->titularDoTenant($id);
            $vendaId = (int) $request->input('venda_id');
            $venda = $this->vendaDoTenant($vendaId);

            if ($titular->venda_id !== $venda->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Titular nao pertence a esta venda.',
                ], 400);
            }

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            $valoresCoparticipacao = $this->valoresCoparticipacaoDaVenda($venda);

            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'nome' => ['required', 'string', 'max:100'],
                'cpf' => ['nullable', 'string', 'max:20'],
                'data_nascimento' => ['nullable', 'string', 'max:10'],
                'email' => ['nullable', 'email', 'max:100'],
                'telefone' => ['nullable', 'string', 'max:20'],
                'telefone2' => ['nullable', 'string', 'max:20'],
                'cargo' => ['nullable', 'string', 'max:50'],
                'plano_id' => [
                    'required',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('operadora_id', $this->operadoraDaVenda($venda)?->id ?? 0),
                ],
                'coparticipacao' => ['nullable', Rule::in($valoresCoparticipacao)],
                'plano_anterior' => ['nullable', Rule::in(['SIM', 'NAO'])],
                'operadora_anterior_id' => ['nullable', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->tenantId())],
            ]);

            DB::transaction(function () use ($titular, $validated) {
                $dataNascimento = null;
                if (! empty($validated['data_nascimento'])) {
                    $partes = explode('/', $validated['data_nascimento']);
                    if (count($partes) === 3) {
                        $dataNascimento = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                $titular->update([
                    'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                    'cpf' => Helpers::cleanSpecialCharacters($validated['cpf'] ?? ''),
                    'data_nascimento' => $dataNascimento,
                    'email' => $validated['email'] ?? null,
                    'telefone' => Helpers::cleanSpecialCharacters($validated['telefone'] ?? ''),
                    'telefone2' => Helpers::cleanSpecialCharacters($validated['telefone2'] ?? ''),
                    'cargo' => $validated['cargo'] ?? null,
                    'plano_id' => (int) $validated['plano_id'],
                    'coparticipacao' => strtoupper($validated['coparticipacao'] ?? ''),
                    'plano_anterior' => $validated['plano_anterior'] ?? 'NAO',
                    'operadora_anterior_id' => ! empty($validated['operadora_anterior_id']) ? (int) $validated['operadora_anterior_id'] : null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Titular atualizado com sucesso.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validacao.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível atualizar o titular neste momento.');
        }
    }

    /**
     * Cria um novo titular no layout PME (AJAX)
     */
    public function storeTitularPME(Request $request)
    {
        try {
            $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
            ]);

            $venda = $this->vendaDoTenant((int) $request->input('venda_id'));

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            $valoresCoparticipacao = $this->valoresCoparticipacaoDaVenda($venda);

            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'nome' => ['required', 'string', 'max:100'],
                'cpf' => ['nullable', 'string', 'max:20'],
                'data_nascimento' => ['nullable', 'string', 'max:10'],
                'email' => ['nullable', 'email', 'max:100'],
                'telefone' => ['nullable', 'string', 'max:20'],
                'telefone2' => ['nullable', 'string', 'max:20'],
                'cargo' => ['nullable', 'string', 'max:50'],
                'plano_id' => [
                    'required',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('operadora_id', $this->operadoraDaVenda($venda)?->id ?? 0),
                ],
                'coparticipacao' => ['nullable', Rule::in($valoresCoparticipacao)],
                'plano_anterior' => ['nullable', Rule::in(['SIM', 'NAO'])],
                'operadora_anterior_id' => ['nullable', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->tenantId())],
            ]);

            $titular = DB::transaction(function () use ($venda, $validated) {
                $dataNascimento = null;
                if (! empty($validated['data_nascimento'])) {
                    $partes = explode('/', $validated['data_nascimento']);
                    if (count($partes) === 3) {
                        $dataNascimento = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                return VendaTitular::create([
                    'venda_id' => $venda->id,
                    'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                    'cpf' => Helpers::cleanSpecialCharacters($validated['cpf'] ?? ''),
                    'data_nascimento' => $dataNascimento,
                    'email' => $validated['email'] ?? null,
                    'telefone' => Helpers::cleanSpecialCharacters($validated['telefone'] ?? ''),
                    'telefone2' => Helpers::cleanSpecialCharacters($validated['telefone2'] ?? ''),
                    'cargo' => $validated['cargo'] ?? null,
                    'plano_id' => (int) $validated['plano_id'],
                    'coparticipacao' => strtoupper($validated['coparticipacao'] ?? ''),
                    'plano_anterior' => $validated['plano_anterior'] ?? 'NAO',
                    'operadora_anterior_id' => ! empty($validated['operadora_anterior_id']) ? (int) $validated['operadora_anterior_id'] : null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Titular cadastrado com sucesso.',
                'titular' => $titular,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validacao.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível cadastrar o titular neste momento.');
        }
    }

    /**
     * Atualiza um dependente no layout PME
     */
    public function updateDependentePME(Request $request, int $id)
    {
        try {
            $dependente = $this->dependenteDoTenant($id);
            $vendaId = (int) $request->input('venda_id');
            $venda = $this->vendaDoTenant($vendaId);

            if ($dependente->venda_id !== $venda->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dependente nao pertence a esta venda.',
                ], 400);
            }

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'nome' => ['required', 'string', 'max:100'],
                'cpf' => ['nullable', 'string', 'max:20'],
                'data_nascimento' => ['nullable', 'string', 'max:10'],
                'email' => ['nullable', 'email', 'max:100'],
                'telefone1' => ['nullable', 'string', 'max:20'],
                'telefone2' => ['nullable', 'string', 'max:20'],
                'parentesco' => ['nullable', 'string', 'max:50'],
                'plano_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('operadora_id', $this->operadoraDaVenda($venda)?->id ?? 0),
                ],
                'coparticipacao' => ['nullable', Rule::in($this->valoresCoparticipacaoDaVenda($venda))],
                'plano_anterior' => ['nullable', Rule::in(['SIM', 'NAO'])],
                'operadora_anterior_id' => ['nullable', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->tenantId())],
            ]);

            DB::transaction(function () use ($dependente, $validated) {
                $dataNascimento = null;
                if (! empty($validated['data_nascimento'])) {
                    $partes = explode('/', $validated['data_nascimento']);
                    if (count($partes) === 3) {
                        $dataNascimento = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                $dependente->update([
                    'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                    'cpf' => Helpers::cleanSpecialCharacters($validated['cpf'] ?? ''),
                    'data_nascimento' => $dataNascimento,
                    'email' => $validated['email'] ?? null,
                    'telefone1' => Helpers::cleanSpecialCharacters($validated['telefone1'] ?? ''),
                    'telefone2' => Helpers::cleanSpecialCharacters($validated['telefone2'] ?? ''),
                    'parentesco' => $validated['parentesco'] ?? null,
                    'plano_id' => ! empty($validated['plano_id']) ? (int) $validated['plano_id'] : null,
                    'coparticipacao' => $validated['coparticipacao'] ?? null,
                    'plano_anterior' => $validated['plano_anterior'] ?? 'NAO',
                    'operadora_anterior_id' => ! empty($validated['operadora_anterior_id']) ? (int) $validated['operadora_anterior_id'] : null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Dependente atualizado com sucesso.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validacao.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível atualizar o dependente neste momento.');
        }
    }

    /**
     * Adiciona um dependente no layout PME
     */
    public function storeDependentePME(Request $request)
    {
        try {
            $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
            ]);
            $venda = $this->vendaDoTenant((int) $request->input('venda_id'));

            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'titular_id' => ['required', 'integer'],
                'nome' => ['required', 'string', 'max:100'],
                'cpf' => ['nullable', 'string', 'max:20'],
                'data_nascimento' => ['nullable', 'string', 'max:10'],
                'email' => ['nullable', 'email', 'max:100'],
                'telefone1' => ['nullable', 'string', 'max:20'],
                'telefone2' => ['nullable', 'string', 'max:20'],
                'parentesco' => ['nullable', 'string', 'max:50'],
                'plano_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('operadora_id', $this->operadoraDaVenda($venda)?->id ?? 0),
                ],
                'coparticipacao' => ['nullable', Rule::in($this->valoresCoparticipacaoDaVenda($venda))],
                'plano_anterior' => ['nullable', Rule::in(['SIM', 'NAO'])],
                'operadora_anterior_id' => ['nullable', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->tenantId())],
            ]);

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            $titular = $this->titularDoTenant((int) $validated['titular_id']);
            if ($titular->venda_id !== $venda->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Titular nao pertence a esta venda.',
                ], 400);
            }

            $dependente = DB::transaction(function () use ($venda, $titular, $validated) {
                $dataNascimento = null;
                if (! empty($validated['data_nascimento'])) {
                    $partes = explode('/', $validated['data_nascimento']);
                    if (count($partes) === 3) {
                        $dataNascimento = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                return VendaDependente::create([
                    'venda_id' => $venda->id,
                    'titular_id' => $titular->id,
                    'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                    'cpf' => Helpers::cleanSpecialCharacters($validated['cpf'] ?? ''),
                    'data_nascimento' => $dataNascimento,
                    'email' => $validated['email'] ?? null,
                    'telefone1' => Helpers::cleanSpecialCharacters($validated['telefone1'] ?? ''),
                    'telefone2' => Helpers::cleanSpecialCharacters($validated['telefone2'] ?? ''),
                    'parentesco' => $validated['parentesco'] ?? null,
                    'plano_id' => ! empty($validated['plano_id']) ? (int) $validated['plano_id'] : null,
                    'coparticipacao' => $validated['coparticipacao'] ?? null,
                    'plano_anterior' => $validated['plano_anterior'] ?? 'NAO',
                    'operadora_anterior_id' => ! empty($validated['operadora_anterior_id']) ? (int) $validated['operadora_anterior_id'] : null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Dependente adicionado com sucesso.',
                'dependente' => $dependente,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validacao.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível adicionar o dependente neste momento.');
        }
    }

    /**
     * Remove um dependente no layout PME
     */
    public function destroyDependentePME(int $id)
    {
        try {
            $dependente = $this->dependenteDoTenant($id);
            $venda = $this->vendaDoTenant($dependente->venda_id);

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            $dependente->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dependente removido com sucesso.',
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível remover o dependente neste momento.');
        }
    }

    /**
     * Adiciona um beneficiário de portabilidade no layout PME
     */
    public function storePortabilidadePME(Request $request)
    {
        try {
            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'nome' => ['required', 'string', 'max:100'],
                'cpf' => ['nullable', 'string', 'max:20'],
                'data_nascimento' => ['nullable', 'string', 'max:10'],
                'operadora_anterior_id' => ['required', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->tenantId())],
                'plano_anterior' => ['nullable', 'string', 'max:100'],
                'numero_carteirinha' => ['nullable', 'string', 'max:50'],
                'operadora_destino_id' => [
                    'required',
                    'integer',
                    Rule::exists('operadoras', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('status', 'Y'),
                ],
                'plano_destino_id' => [
                    'required',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('status', 'Y')
                        ->where('operadora_id', $request->integer('operadora_destino_id')),
                ],
            ]);

            $venda = $this->vendaDoTenant((int) $validated['venda_id']);

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            $portabilidade = DB::transaction(function () use ($venda, $validated) {
                $dataNascimento = null;
                if (! empty($validated['data_nascimento'])) {
                    $partes = explode('/', $validated['data_nascimento']);
                    if (count($partes) === 3) {
                        $dataNascimento = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                // Calcula o próximo sequencial
                $maxSequencial = VendaPortabilidade::where('venda_id', $venda->id)->max('sequencial') ?? 0;

                $portabilidade = VendaPortabilidade::create([
                    'venda_id' => $venda->id,
                    'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                    'cpf' => Helpers::cleanSpecialCharacters($validated['cpf'] ?? ''),
                    'data_nascimento' => $dataNascimento,
                    'operadora_anterior_id' => (int) $validated['operadora_anterior_id'],
                    'plano_anterior' => $validated['plano_anterior'] ?? null,
                    'numero_carteirinha' => $validated['numero_carteirinha'] ?? null,
                    'operadora_destino_id' => (int) $validated['operadora_destino_id'],
                    'plano_destino_id' => (int) $validated['plano_destino_id'],
                    'sequencial' => $maxSequencial + 1,
                ]);

                $quantidade = VendaPortabilidade::where('venda_id', $venda->id)->count();
                $venda->update([
                    'portabilidade_status' => 'SIM',
                    'qtd_portabilidade' => $quantidade,
                ]);

                return $portabilidade;
            });

            return response()->json([
                'success' => true,
                'message' => 'Beneficiário adicionado com sucesso.',
                'portabilidade' => $portabilidade,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível adicionar o beneficiário neste momento.');
        }
    }

    /**
     * Atualiza um beneficiário de portabilidade no layout PME
     */
    public function updatePortabilidadePME(Request $request, int $id)
    {
        try {
            $portabilidade = $this->portabilidadeDoTenant($id);
            $vendaId = (int) $request->input('venda_id');
            $venda = $this->vendaDoTenant($vendaId);

            if ($portabilidade->venda_id !== $venda->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Beneficiário não pertence a esta venda.',
                ], 400);
            }

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'nome' => ['required', 'string', 'max:100'],
                'cpf' => ['nullable', 'string', 'max:20'],
                'data_nascimento' => ['nullable', 'string', 'max:10'],
                'operadora_anterior_id' => ['required', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->tenantId())],
                'plano_anterior' => ['nullable', 'string', 'max:100'],
                'numero_carteirinha' => ['nullable', 'string', 'max:50'],
                'operadora_destino_id' => [
                    'required',
                    'integer',
                    Rule::exists('operadoras', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('status', 'Y'),
                ],
                'plano_destino_id' => [
                    'required',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('status', 'Y')
                        ->where('operadora_id', $request->integer('operadora_destino_id')),
                ],
            ]);

            DB::transaction(function () use ($portabilidade, $validated) {
                $dataNascimento = null;
                if (! empty($validated['data_nascimento'])) {
                    $partes = explode('/', $validated['data_nascimento']);
                    if (count($partes) === 3) {
                        $dataNascimento = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                    }
                }

                $portabilidade->update([
                    'nome' => mb_strtoupper(trim($validated['nome']), 'UTF-8'),
                    'cpf' => Helpers::cleanSpecialCharacters($validated['cpf'] ?? ''),
                    'data_nascimento' => $dataNascimento,
                    'operadora_anterior_id' => (int) $validated['operadora_anterior_id'],
                    'plano_anterior' => $validated['plano_anterior'] ?? null,
                    'numero_carteirinha' => $validated['numero_carteirinha'] ?? null,
                    'operadora_destino_id' => (int) $validated['operadora_destino_id'],
                    'plano_destino_id' => (int) $validated['plano_destino_id'],
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Beneficiário atualizado com sucesso.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível atualizar o beneficiário neste momento.');
        }
    }

    /**
     * Remove um beneficiário de portabilidade no layout PME
     */
    public function destroyPortabilidadePME(int $id)
    {
        try {
            $portabilidade = $this->portabilidadeDoTenant($id);
            $venda = $this->vendaDoTenant($portabilidade->venda_id);

            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado para esta venda.',
                ], 403);
            }

            DB::transaction(function () use ($portabilidade, $venda) {
                $portabilidade->delete();
                $quantidade = VendaPortabilidade::where('venda_id', $venda->id)->count();
                $venda->update([
                    'portabilidade_status' => $quantidade > 0 ? 'SIM' : 'NAO',
                    'qtd_portabilidade' => $quantidade,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Beneficiário removido com sucesso.',
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível remover o beneficiário neste momento.');
        }
    }

    public function storeTitular(Request $request)
    {
        try {
            // 1) valida apenas venda_id para poder decidir regra dinâmica
            $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
            ]);

            $venda = $this->vendaDoTenant((int) $request->input('venda_id'));

            // segura: venda precisa pertencer à mesma empresa do usuário
            if ((int) $venda->empresa_id !== (int) $this->tenantId()) {
                return back()
                    ->withInput()
                    ->with('status', 'error')
                    ->with('message', 'Acesso negado para esta venda.');
            }

            $valoresCoparticipacao = $this->valoresCoparticipacaoDaVenda($venda);

            // 2) validação completa agora que sabemos a regra de coparticipação
            $validated = $request->validate([
                'nome' => ['required', 'string', 'max:90'],
                'email' => ['nullable', 'email', 'max:90'],
                'telefone' => ['nullable', 'string', 'max:50'],
                'plano_id' => [
                    'required',
                    'integer',
                    Rule::exists('planos', 'id')
                        ->where('empresa_id', $this->tenantId())
                        ->where('operadora_id', $this->operadoraDaVenda($venda)?->id ?? 0),
                ],
                'coparticipacao' => ['required', Rule::in($valoresCoparticipacao)],
            ]);

            DB::transaction(function () use ($venda, $validated) {
                VendaTitular::create([
                    'venda_id' => $venda->id,
                    'nome' => mb_strtoupper($validated['nome'], 'UTF-8'),
                    'email' => $validated['email'] ?? null,
                    'telefone' => Helpers::cleanSpecialCharacters($validated['telefone'] ?? ''),
                    'plano_id' => (int) $validated['plano_id'],
                    'coparticipacao' => strtoupper($validated['coparticipacao']),
                ]);
            });

            return back()
                ->with('status', 'success')
                ->with('message', 'Titular cadastrado com sucesso.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Erro de validação.');
        } catch (\Throwable $e) {
            // \Log::error('Erro ao criar titular', ['e' => $e]);
            return back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Falha ao cadastrar titular.');
        }
    }

    /**
     * Gera ou atualiza recebíveis para um contrato específico
     */
    public function gerarRecebivelContrato(int $vendaId)
    {
        try {
            $empresaId = $this->tenantId();

            // Buscar a venda e validar
            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->where('tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO))
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrato não encontrado ou não está implantado.',
                ], 404);
            }

            if (! $venda->data_implantacao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrato não possui data de implantação definida.',
                ], 400);
            }

            // Buscar operadora
            $operadora = Operadora::where('empresa_id', $empresaId)
                ->where('nome', $venda->operadora)
                ->first();

            if (! $operadora) {
                return response()->json([
                    'success' => false,
                    'message' => "Operadora não encontrada: {$venda->operadora}",
                ], 400);
            }

            // Buscar regra de comissionamento
            $regra = RegrasComissionamento::with('parcelas')
                ->where('empresa_id', $venda->empresa_id)
                ->where('operadora_id', $operadora->id)
                ->first();

            if (! $regra) {
                return response()->json([
                    'success' => false,
                    'message' => "Nenhuma regra de comissionamento encontrada para a operadora {$operadora->nome}",
                ], 400);
            }

            // Resolver nome do plano
            $planoNome = 'N/A';
            if (! empty($venda->plano_id)) {
                $plano = Plano::where('empresa_id', $empresaId)->find($venda->plano_id);
                $planoNome = $plano?->nome ?? $venda->nome_plano ?? 'N/A';
            } elseif (! empty($venda->nome_plano)) {
                $planoNome = $venda->nome_plano;
            }

            // Verificar se já existem recebíveis
            $recebiveisExistentes = Recebivel::where('venda_id', $vendaId)->count();

            // Gerar ou atualizar recebíveis conforme parcelas da regra
            $criados = 0;
            $atualizados = 0;

            foreach ($regra->parcelas()->orderBy('parcela')->get() as $parcela) {
                $valorParcela = ($parcela->percentual / 100) * $venda->valor_contrato;

                $recebivel = Recebivel::updateOrCreate(
                    [
                        'venda_id' => $venda->id,
                        'parcela' => $parcela->parcela,
                    ],
                    [
                        'empresa_id' => $venda->empresa_id,
                        'vendedor_id' => $venda->user_id,
                        'operadora' => $operadora->nome,
                        'plano' => $planoNome,
                        'vitalicio' => false,
                        'valor' => $valorParcela,
                        'data_prevista' => Carbon::parse($venda->data_implantacao)->addMonths($parcela->parcela - 1),
                        'status' => 'PENDENTE',
                    ]
                );

                if ($recebivel->wasRecentlyCreated) {
                    $criados++;
                } else {
                    $atualizados++;
                }
            }

            // Montar mensagem de retorno
            $mensagem = '';
            if ($criados > 0 && $atualizados > 0) {
                $mensagem = "{$criados} recebível(is) criado(s) e {$atualizados} atualizado(s) com sucesso.";
            } elseif ($criados > 0) {
                $mensagem = "{$criados} recebível(is) gerado(s) com sucesso.";
            } else {
                $mensagem = "{$atualizados} recebível(is) atualizado(s) com sucesso.";
            }

            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'criados' => $criados,
                'atualizados' => $atualizados,
                'ja_existia' => $recebiveisExistentes > 0,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível gerar os recebíveis neste momento.');
        }
    }

    /**
     * Verifica se um contrato possui recebíveis
     */
    public function verificarRecebiveis(int $vendaId)
    {
        try {
            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrato não encontrado.',
                ], 404);
            }

            $recebiveis = Recebivel::where('venda_id', $vendaId)->get();
            $totalRecebiveis = $recebiveis->count();
            $valorTotal = $recebiveis->sum('valor');

            return response()->json([
                'success' => true,
                'possui_recebiveis' => $totalRecebiveis > 0,
                'quantidade' => $totalRecebiveis,
                'valor_total' => $valorTotal,
                'nome_contrato' => $venda->nome_contrato,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível verificar os recebíveis neste momento.');
        }
    }

    /**
     * Relatório de Performance do Backoffice
     * Analisa tempo médio entre status e gargalos no pipeline
     */
    public function assumirContrato(Request $request)
    {
        $request->validate(['venda_id' => 'required|integer']);

        $user = Auth::user();
        if ($user->user_role_id != UserRole::BACKOFFICE) {
            return response()->json(['success' => false, 'message' => 'Apenas usuarios backoffice podem assumir contratos.'], 403);
        }

        $sale = $this->vendasRepository->find($request->venda_id);
        if (! $sale || $sale->empresa_id != $this->tenantId()) {
            return response()->json(['success' => false, 'message' => 'Contrato nao encontrado.'], 404);
        }

        if ($sale->backoffice_id !== null) {
            $responsavel = User::query()->tenantMember($this->tenantId())->find($sale->backoffice_id);

            return response()->json([
                'success' => false,
                'message' => 'Este contrato ja possui um responsavel: '.($responsavel->name ?? 'Desconhecido'),
            ], 409);
        }

        $sale->backoffice_id = $user->id;
        $sale->save();

        return response()->json(['success' => true, 'message' => 'Contrato assumido com sucesso!']);
    }

    public function reatribuirContrato(Request $request)
    {
        $request->validate([
            'venda_id' => 'required|integer',
            'backoffice_id' => 'nullable|integer',
        ]);

        if (! $this->canReassignContract()) {
            return response()->json(['success' => false, 'message' => 'Voce nao tem permissao para reatribuir contratos.'], 403);
        }

        $sale = $this->vendasRepository->find($request->venda_id);
        if (! $sale || $sale->empresa_id != $this->tenantId()) {
            return response()->json(['success' => false, 'message' => 'Contrato nao encontrado.'], 404);
        }

        if ($request->backoffice_id) {
            $novoBackoffice = User::query()
                ->tenantMember($this->tenantId())
                ->where('id', $request->backoffice_id)
                ->whereIn('user_role_id', [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])
                ->first();

            if (! $novoBackoffice) {
                return response()->json(['success' => false, 'message' => 'Usuario backoffice nao encontrado.'], 404);
            }
        }

        $sale->backoffice_id = $request->backoffice_id;
        $sale->save();

        return response()->json(['success' => true, 'message' => 'Contrato reatribuido com sucesso!']);
    }

    /**
     * Status de destino aceitos ao retomar um estorno. São os estágios do fluxo
     * normal da fila — IMPLANTADO/PENDENCIA/ESTORNO exigem dados extras (modal)
     * e DECLINADO é saída, não retomada.
     */
    /**
     * Propostas estornadas da empresa, do estorno mais recente para o mais antigo —
     * o que acabou de voltar é o que ainda dá para captar. Alimenta o painel de
     * estornos da fila.
     */
    public function listaEstornos()
    {
        $user = Auth::user();

        $vendas = DB::table('vendas')
            ->select([
                'vendas.id',
                'vendas.numero_proposta',
                'vendas.nome_contrato',
                'vendas.cpf_cnpj',
                'vendas.operadora',
                'vendas.nome_plano',
                'vendas.valor_contrato',
                'vendas.vidas',
                'vendas.motivo_pendencia',
                'vendas.backoffice_id',
                'vendas.tabulacao_updated_at',
                'vendas.updated_at',
                'users.name as vendedor_nome',
                'backoffice_user.name as backoffice_nome',
            ])
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'vendas.user_id')
                    ->on('users.empresa_id', '=', 'vendas.empresa_id')
                    ->where('users.is_platform_admin', false);
            })
            ->leftJoin('users as backoffice_user', function ($join) {
                $join->on('backoffice_user.id', '=', 'vendas.backoffice_id')
                    ->on('backoffice_user.empresa_id', '=', 'vendas.empresa_id')
                    ->where('backoffice_user.is_platform_admin', false);
            })
            ->where('vendas.empresa_id', $this->tenantId())
            ->where('vendas.tabulacao_id', $this->tabulationId(TabulationCode::ESTORNO))
            ->get();

        // Quem estornou vem do histórico; a data do estorno usa tabulacao_updated_at
        // (gravada na troca de status) e cai para o histórico em registros antigos.
        $historicos = VendaHistorico::whereIn('venda_id', $vendas->pluck('id'))
            ->where('tabulacao_nova_id', $this->tabulationId(TabulationCode::ESTORNO))
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('venda_id');

        $isBackoffice = $user->user_role_id == UserRole::BACKOFFICE;

        $estornos = $vendas->map(function ($v) use ($historicos, $user, $isBackoffice) {
            $hist = $historicos->get($v->id)?->first();
            $estornadoEm = $v->tabulacao_updated_at ?? $hist?->created_at ?? $v->updated_at;
            $estornadoEm = $estornadoEm ? Carbon::parse($estornadoEm) : null;

            // BACKOFFICE retoma o que é dele ou o que está livre; ADM/DEV retomam tudo.
            $podeRetomar = ! $isBackoffice
                || $v->backoffice_id === null
                || (int) $v->backoffice_id === $user->id;

            return [
                'id' => $v->id,
                'numero_proposta' => $v->numero_proposta,
                'nome_contrato' => $v->nome_contrato,
                'cpf_cnpj' => $v->cpf_cnpj,
                'operadora' => $v->operadora,
                'plano' => $v->nome_plano,
                'valor' => $v->valor_contrato,
                'vidas' => $v->vidas,
                'motivo' => $v->motivo_pendencia ?: $hist?->motivo_pendencia,
                'vendedor' => $v->vendedor_nome,
                'backoffice_nome' => $v->backoffice_nome,
                'estornado_em' => $estornadoEm?->format('d/m/Y'),
                'dias_parado' => $estornadoEm ? (int) $estornadoEm->diffInDays(now()) : 0,
                'pode_retomar' => $podeRetomar,
                'ordem' => [$estornadoEm?->getTimestamp() ?? 0, $v->id],
            ];
        })
            ->sortByDesc('ordem')
            ->values()
            ->map(function ($e) {
                unset($e['ordem']);

                return $e;
            });

        return response()->json([
            'success' => true,
            'total' => $estornos->count(),
            'estornos' => $estornos,
        ]);
    }

    /**
     * Puxa de volta para a fila do backoffice uma venda que está em ESTORNO,
     * sem depender do reenvio do vendedor. Usado quando o alinhamento foi feito
     * por outro canal ou o estorno saiu por engano.
     */
    public function retomarEstorno(Request $request)
    {
        $request->validate([
            'venda_id' => 'required|integer',
            'tabulacao_id' => [
                'nullable',
                'integer',
                Rule::exists('tabulacoes', 'id')
                    ->where('empresa_id', $this->tenantId())
                    ->where('tipo_tabulacao', 'A'),
            ],
            'observacao' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        if (! in_array($user->user_role_id, [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true)) {
            return response()->json(['success' => false, 'message' => 'Voce nao tem permissao para retomar contratos estornados.'], 403);
        }

        $sale = $this->vendasRepository->find($request->venda_id);
        if (! $sale || $sale->empresa_id != $this->tenantId()) {
            return response()->json(['success' => false, 'message' => 'Contrato nao encontrado.'], 404);
        }

        if ((int) $sale->tabulacao_id !== $this->tabulationId(TabulationCode::ESTORNO)) {
            return response()->json(['success' => false, 'message' => 'Esta proposta nao esta em estorno.'], 409);
        }

        // BACKOFFICE retoma o que é dele ou o que está sem dono (e assume a custódia
        // no mesmo passo). ADM/DEVELOPER retomam qualquer contrato da empresa.
        $isBackoffice = $user->user_role_id == UserRole::BACKOFFICE;
        if ($isBackoffice && $sale->backoffice_id !== null && $sale->backoffice_id !== $user->id) {
            $responsavel = User::query()->tenantMember($this->tenantId())->find($sale->backoffice_id);

            return response()->json([
                'success' => false,
                'message' => 'Este contrato esta sob responsabilidade de '.($responsavel->name ?? 'outro backoffice').'.',
            ], 403);
        }

        $destino = (int) ($request->input('tabulacao_id') ?: $this->tabulationId(TabulationCode::VENDA));
        if (! in_array($destino, $this->destinosRetomadaEstorno(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Status de destino invalido para retomada. Traga para a fila e siga o fluxo normal.',
            ], 422);
        }

        $observacao = trim((string) $request->input('observacao')) ?: null;

        try {
            DB::beginTransaction();

            $ok = $this->vendasRepository->alterStatusVenda($sale->id, $destino, $observacao);

            if (! $ok) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Nao foi possivel retomar a proposta.'], 500);
            }

            // Motivo do estorno some da venda (fica no histórico) e o backoffice
            // sem dono passa a ser quem retomou.
            $updates = ['motivo_pendencia' => null, 'updated_at' => now()];
            if ($sale->backoffice_id === null && $isBackoffice) {
                $updates['backoffice_id'] = $user->id;
            }
            Vendas::where('empresa_id', $this->tenantId())
                ->where('id', $sale->id)
                ->update($updates);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return $this->internalError($th, 'Não foi possível retomar a proposta neste momento.');
        }

        $tabulacao = Tabulacoes::where('empresa_id', $this->tenantId())->find($destino);
        $novoStatus = $tabulacao->descricao ?? 'Etapa atualizada';

        // O vendedor precisa saber que a venda saiu de "Meus Estornos" e que o
        // reenvio dele não é mais necessário.
        $vendedor = User::query()->tenantMember($this->tenantId())->find($sale->user_id);
        if ($vendedor) {
            $vendedor->notify(new VendaRetomadaPeloBackoffice(
                vendaId: $sale->id,
                nomeContrato: $sale->nome_contrato ?? "#{$sale->id}",
                novoStatus: $novoStatus,
                backofficeNome: $user->name ?? null,
                observacao: $observacao,
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Proposta retomada para a fila do backoffice.',
            'novo_status' => $novoStatus,
        ]);
    }

    /**
     * Neutraliza curingas do LIKE — "%" ou "_" digitados viravam wildcard.
     */
    private function escaparLike(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }

    /**
     * Busca por palavras soltas, em qualquer ordem: "empresa x10" precisa achar
     * "X10 COMERCIO E AUTOMACAO LTDA". Cada palavra casa contra nome, proposta,
     * operadora ou documento; basta uma bater. Sem isso o LIKE exigia a frase
     * inteira contígua e a fila parecia vazia mesmo com o contrato na base.
     */
    private function aplicarBuscaContratos($query, ?string $termo): void
    {
        $termo = trim(preg_replace('/\s+/', ' ', (string) $termo));
        if ($termo === '') {
            return;
        }

        $palavras = array_slice(array_filter(explode(' ', $termo), fn ($p) => $p !== ''), 0, 6);
        $digitos = preg_replace('/\D+/', '', $termo);

        $query->where(function ($q) use ($palavras, $digitos) {
            foreach ($palavras as $palavra) {
                $like = '%'.$this->escaparLike($palavra).'%';
                $q->orWhere('vendas.nome_contrato', 'like', $like)
                    ->orWhere('vendas.numero_proposta', 'like', $like)
                    ->orWhere('vendas.operadora', 'like', $like);

                $digitosPalavra = preg_replace('/\D+/', '', $palavra);
                if ($digitosPalavra !== '') {
                    $q->orWhere('vendas.cpf_cnpj', 'like', '%'.$digitosPalavra.'%');
                }
            }

            if ($digitos !== '') {
                $q->orWhere('vendas.cpf_cnpj', 'like', '%'.$digitos.'%');
            }
        });
    }

    /**
     * Contratos que casam a busca mas estão em status que não têm raia na fila
     * (IMPLANTADO, por exemplo). Sem esse aviso, procurar um contrato implantado
     * na fila devolve "nenhum resultado" como se ele não existisse.
     */
    private function contratosForaDaFila(?string $busca, int $empresaId, $idsKanban): array
    {
        if (trim((string) $busca) === '') {
            return [];
        }

        $query = DB::table('vendas')
            ->select([
                'vendas.id',
                'vendas.nome_contrato',
                'vendas.numero_proposta',
                'vendas.cpf_cnpj',
                'tabulacoes.descricao as status_atual',
            ])
            ->leftJoin('tabulacoes', function ($join) {
                $join->on('tabulacoes.id', '=', 'vendas.tabulacao_id')
                    ->on('tabulacoes.empresa_id', '=', 'vendas.empresa_id');
            })
            ->where('vendas.empresa_id', $empresaId)
            ->whereNotIn('vendas.tabulacao_id', $idsKanban);

        $this->aplicarBuscaContratos($query, $busca);

        return $query->orderByDesc('vendas.id')
            ->limit(5)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'numero_proposta' => $v->numero_proposta,
                'status_atual' => $v->status_atual ?? '—',
            ])
            ->all();
    }

    /**
     * Retorna dados para o pipeline visual
     */
    public function getPipelineData(Request $request)
    {
        try {
            $empresaId = $this->tenantId();
            $dataInicio = $request->input('data_inicio');
            $dataFim = $request->input('data_fim');
            $vendedorId = $request->input('vendedor_id');
            $busca = $request->input('busca');
            $custodia = $request->input('custodia', '');
            $backofficeId = $request->input('backoffice_id');
            $isBackoffice = Auth::user()->user_role_id == UserRole::BACKOFFICE;

            // Buscar vendas com status de backoffice — o status do contrato
            // vive na própria venda (vendas.tabulacao_id)
            $query = DB::table('vendas')
                ->select([
                    'vendas.id',
                    'vendas.numero_proposta',
                    'vendas.nome_contrato',
                    'vendas.operadora',
                    'vendas.nome_plano',
                    'vendas.valor_contrato',
                    'vendas.angariacao_status',
                    'vendas.angariacao_valor',
                    'vendas.created_at as data_venda',
                    'vendas.data_implantacao',
                    'vendas.contato_id',
                    'vendas.motivo_pendencia',
                    'vendas.backoffice_id',
                    'backoffice_user.name as backoffice_nome',
                    'users.name as vendedor',
                    'users.id as vendedor_id',
                    'tabulacoes.id as tabulacao_id',
                    'tabulacoes.descricao as status_atual',
                    'tabulacoes.codigo as status_codigo',
                    'tabulacoes.ordem_kanban',
                    'vendas.tabulacao_updated_at as status_updated_at',
                ])
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'vendas.user_id')
                        ->on('users.empresa_id', '=', 'vendas.empresa_id')
                        ->where('users.is_platform_admin', false);
                })
                ->leftJoin('users as backoffice_user', function ($join) {
                    $join->on('backoffice_user.id', '=', 'vendas.backoffice_id')
                        ->on('backoffice_user.empresa_id', '=', 'vendas.empresa_id')
                        ->where('backoffice_user.is_platform_admin', false);
                })
                ->leftJoin('tabulacoes', function ($join) {
                    $join->on('tabulacoes.id', '=', 'vendas.tabulacao_id')
                        ->on('tabulacoes.empresa_id', '=', 'vendas.empresa_id');
                })
                ->where('vendas.empresa_id', $empresaId)
                ->where('tabulacoes.tipo_tabulacao', 'A'); // Apenas tabulações de backoffice

            if ($dataInicio) {
                $query->whereDate('vendas.created_at', '>=', $dataInicio);
            }
            if ($dataFim) {
                $query->whereDate('vendas.created_at', '<=', $dataFim);
            }
            if ($vendedorId) {
                $query->where('vendas.user_id', $vendedorId);
            }
            $this->aplicarBuscaContratos($query, $busca);

            // Filtro de custodia: backoffice ve apenas seus contratos por padrao
            if ($isBackoffice && $custodia !== 'todos') {
                $query->where(function ($q) {
                    $q->where('vendas.backoffice_id', Auth::id())
                        ->orWhereNull('vendas.backoffice_id');
                });
            }

            // Filtro por responsável (backoffice_id) — usado pelo admin
            if ($backofficeId) {
                if ($backofficeId === 'sem') {
                    $query->whereNull('vendas.backoffice_id');
                } else {
                    $query->where('vendas.backoffice_id', $backofficeId);
                }
            }

            $vendas = $query->orderBy('vendas.created_at', 'desc')->get();

            // Contagem de demandas pendentes por venda
            $vendaIds = $vendas->pluck('id')->toArray();
            $demandasPendentesMap = [];
            if (! empty($vendaIds)) {
                $demandasPendentesMap = DB::table('venda_demandas')
                    ->select('venda_id', DB::raw('COUNT(*) as total'))
                    ->whereIn('venda_id', $vendaIds)
                    ->where('status', 'PENDENTE')
                    ->groupBy('venda_id')
                    ->pluck('total', 'venda_id')
                    ->toArray();
            }

            // Status permitidos no Kanban (ordem definida pelo usuário)
            $codigosPermitidos = [
                TabulationCode::VENDA,
                TabulationCode::ANALISE_DOCUMENTOS,
                TabulationCode::AGUARDANDO_ASSINATURA_DS,
                TabulationCode::ESTORNO,
                TabulationCode::PENDENCIA,
                TabulationCode::ANALISE_OPERADORA,
                TabulationCode::CONTRATO_GERADO_AGUARDANDO_ASSINATURA,
                TabulationCode::BOLETO_DISPONIVEL,
                TabulationCode::REGULARIZADO,
                TabulationCode::DECLINADO,
            ];

            // Buscar tabulações do backoffice APENAS dos status permitidos
            $tabulacoes = Tabulacoes::where('empresa_id', $empresaId)
                ->where('tipo_tabulacao', 'A')
                ->where('status', 'Y')
                ->whereIn('codigo', $codigosPermitidos)
                ->get()
                ->sortBy(function ($tab) use ($codigosPermitidos) {
                    // A ordem operacional é semântica; o rótulo pode ser personalizado.
                    return array_search($tab->codigo, $codigosPermitidos, true);
                })
                ->values();

            // Agrupar vendas por status
            $pipeline = [];
            foreach ($tabulacoes as $tab) {
                $vendasNoStatus = $vendas->where('tabulacao_id', $tab->id);

                $pipeline[] = [
                    'id' => $tab->id,
                    'codigo' => $tab->codigo,
                    'nome' => $tab->descricao,
                    'ordem' => $tab->ordem_kanban,
                    'cor' => $this->getCorStatus($tab->codigo),
                    'icone' => $this->getIconeStatus($tab->codigo),
                    'quantidade' => $vendasNoStatus->count(),
                    'valor_total' => $vendasNoStatus->sum(function ($v) {
                        $valor = (float) ($v->valor_contrato ?? 0);
                        if (($v->angariacao_status ?? '') === 'SIM') {
                            $valor += (float) ($v->angariacao_valor ?? 0);
                        }

                        return $valor;
                    }),
                    'contratos' => $vendasNoStatus->map(function ($v) {
                        $diasNaFila = $v->data_venda
                            ? (int) Carbon::parse($v->data_venda)->diffInDays(now())
                            : 0;

                        return [
                            'id' => $v->id,
                            'venda_id' => $v->id, // Alias para compatibilidade
                            'numero_proposta' => $v->numero_proposta,
                            'nome_contrato' => $v->nome_contrato,
                            'operadora' => $v->operadora,
                            'plano' => $v->nome_plano,
                            'valor' => $v->valor_contrato,
                            'vendedor' => $v->vendedor,
                            'vendedor_id' => $v->vendedor_id,
                            'data_venda' => Carbon::parse($v->data_venda)->format('d/m/Y'),
                            'dias_na_fila' => $diasNaFila,
                            'motivo_pendencia' => $v->motivo_pendencia,
                            'contato_id' => $v->contato_id,
                            'backoffice_id' => $v->backoffice_id,
                            'backoffice_nome' => $v->backoffice_nome,
                            'demandas_pendentes' => $demandasPendentesMap[$v->id] ?? 0,
                        ];
                    })->values(),
                ];
            }

            // KPIs
            $total = $vendas->count();
            $implantados = $vendas->where('status_codigo', TabulationCode::IMPLANTADO)->count();
            $emAndamento = $vendas->whereNotIn('status_codigo', [TabulationCode::IMPLANTADO, TabulationCode::ESTORNO, TabulationCode::DECLINADO])->count();
            $perdidos = $vendas->whereIn('status_codigo', [TabulationCode::ESTORNO, TabulationCode::DECLINADO])->count();

            // Tempo médio de implantação
            $temposImplantacao = [];
            foreach ($vendas->where('status_codigo', TabulationCode::IMPLANTADO) as $v) {
                if ($v->data_implantacao && $v->data_venda) {
                    $temposImplantacao[] = Carbon::parse($v->data_venda)->diffInDays(Carbon::parse($v->data_implantacao));
                }
            }
            $tempoMedio = count($temposImplantacao) > 0 ? round(array_sum($temposImplantacao) / count($temposImplantacao), 1) : 0;

            // Vendedores para filtro - busca vendedores que têm vendas no sistema
            $vendedores = User::query()->tenantMember($empresaId)
                ->select('users.id', 'users.name')
                ->join('vendas', function ($join) {
                    $join->on('vendas.user_id', '=', 'users.id')
                        ->on('vendas.empresa_id', '=', 'users.empresa_id');
                })
                ->where('vendas.empresa_id', $empresaId)
                ->distinct()
                ->orderBy('users.name')
                ->get();

            // Backoffices para filtro (admin) - busca usuários backoffice que são responsáveis por contratos
            $backoffices = User::query()->tenantMember($empresaId)
                ->select('users.id', 'users.name')
                ->join('vendas', function ($join) {
                    $join->on('vendas.backoffice_id', '=', 'users.id')
                        ->on('vendas.empresa_id', '=', 'users.empresa_id');
                })
                ->where('vendas.empresa_id', $empresaId)
                ->distinct()
                ->orderBy('users.name')
                ->get();

            return response()->json([
                'success' => true,
                'pipeline' => $pipeline,
                'kpis' => [
                    'total' => $total,
                    'implantados' => $implantados,
                    'em_andamento' => $emAndamento,
                    'perdidos' => $perdidos,
                    'taxa_conversao' => $total > 0 ? round(($implantados / $total) * 100, 1) : 0,
                    'tempo_medio' => $tempoMedio,
                    'valor_total' => $vendas->sum('valor_contrato'),
                    'valor_implantado' => $vendas->where('status_codigo', TabulationCode::IMPLANTADO)->sum('valor_contrato'),
                    'total_demandas_pendentes' => array_sum($demandasPendentesMap),
                    'contratos_com_demandas' => count($demandasPendentesMap),
                ],
                'vendedores' => $vendedores,
                'backoffices' => $backoffices,
                'tabulacoes' => $tabulacoes,
                'isBackoffice' => $isBackoffice,
                'fora_da_fila' => $this->contratosForaDaFila($busca, $empresaId, $tabulacoes->pluck('id')->all()),
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar o pipeline neste momento.');
        }
    }

    /**
     * Retorna demandas pendentes para o painel overview do kanban
     */
    public function getDemandasPendentesKanban(Request $request)
    {
        try {
            $empresaId = $this->tenantId();
            $dataInicio = $request->input('data_inicio');
            $dataFim = $request->input('data_fim');
            $vendedorId = $request->input('vendedor_id');
            $busca = $request->input('busca');
            $custodia = $request->input('custodia', '');
            $backofficeId = $request->input('backoffice_id');
            $isBackoffice = Auth::user()->user_role_id == UserRole::BACKOFFICE;

            // Reconstruir query base para obter IDs de vendas visíveis (mesmos filtros do kanban)
            $query = DB::table('vendas')
                ->select('vendas.id')
                ->leftJoin('tabulacoes', function ($join) {
                    $join->on('tabulacoes.id', '=', 'vendas.tabulacao_id')
                        ->on('tabulacoes.empresa_id', '=', 'vendas.empresa_id');
                })
                ->where('vendas.empresa_id', $empresaId)
                ->where('tabulacoes.tipo_tabulacao', 'A');

            if ($dataInicio) {
                $query->whereDate('vendas.created_at', '>=', $dataInicio);
            }
            if ($dataFim) {
                $query->whereDate('vendas.created_at', '<=', $dataFim);
            }
            if ($vendedorId) {
                $query->where('vendas.user_id', $vendedorId);
            }
            $this->aplicarBuscaContratos($query, $busca);

            if ($isBackoffice && $custodia !== 'todos') {
                $query->where(function ($q) {
                    $q->where('vendas.backoffice_id', Auth::id())
                        ->orWhereNull('vendas.backoffice_id');
                });
            }

            // Filtro por responsável (backoffice_id) — usado pelo admin
            if ($backofficeId) {
                if ($backofficeId === 'sem') {
                    $query->whereNull('vendas.backoffice_id');
                } else {
                    $query->where('vendas.backoffice_id', $backofficeId);
                }
            }

            $vendaIds = $query->pluck('vendas.id')->toArray();

            if (empty($vendaIds)) {
                return response()->json(['success' => true, 'demandas' => []]);
            }

            // Buscar info extra dos contratos (backoffice, status)
            $vendaInfoMap = DB::table('vendas')
                ->select('vendas.id', 'vendas.backoffice_id', 'bo.name as backoffice_nome', 'tabulacoes.descricao as status_atual')
                ->leftJoin('users as bo', function ($join) {
                    $join->on('bo.id', '=', 'vendas.backoffice_id')
                        ->on('bo.empresa_id', '=', 'vendas.empresa_id')
                        ->where('bo.is_platform_admin', false);
                })
                ->leftJoin('tabulacoes', function ($join) {
                    $join->on('tabulacoes.id', '=', 'vendas.tabulacao_id')
                        ->on('tabulacoes.empresa_id', '=', 'vendas.empresa_id');
                })
                ->whereIn('vendas.id', $vendaIds)
                ->get()
                ->keyBy('id');

            // Contratos que ainda têm pelo menos 1 demanda pendente
            $vendasComPendente = VendaDemanda::where('empresa_id', $empresaId)
                ->where('status', 'PENDENTE')
                ->whereIn('venda_id', $vendaIds)
                ->distinct()
                ->pluck('venda_id')
                ->toArray();

            if (empty($vendasComPendente)) {
                return response()->json(['success' => true, 'demandas' => []]);
            }

            // Buscar TODAS as demandas (pendentes + concluídas) desses contratos
            $demandas = VendaDemanda::with([
                'criador' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
                'venda' => fn ($query) => $query->select('id', 'nome_contrato', 'numero_proposta', 'operadora')->where('vendas.empresa_id', $empresaId),
            ])
                ->where('empresa_id', $empresaId)
                ->whereIn('venda_id', $vendasComPendente)
                ->orderByRaw("FIELD(status, 'PENDENTE', 'CONCLUIDA')")
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($d) use ($vendaInfoMap) {
                    $rawCreatedAt = $d->getRawOriginal('created_at');
                    $diasPendente = $rawCreatedAt ? (int) \Carbon\Carbon::parse($rawCreatedAt)->diffInDays(now()) : 0;
                    $vendaInfo = $vendaInfoMap[$d->venda_id] ?? null;

                    return [
                        'id' => $d->id,
                        'venda_id' => $d->venda_id,
                        'tipo' => $d->tipo,
                        'titulo' => $d->titulo,
                        'status' => $d->status,
                        'criador' => $d->criador ? $d->criador->name : 'N/A',
                        'dias_pendente' => $diasPendente,
                        'contrato_nome' => $d->venda ? $d->venda->nome_contrato : 'N/A',
                        'contrato_proposta' => $d->venda ? $d->venda->numero_proposta : null,
                        'contrato_operadora' => $d->venda ? $d->venda->operadora : null,
                        'backoffice_nome' => $vendaInfo->backoffice_nome ?? null,
                        'status_atual' => $vendaInfo->status_atual ?? null,
                        'created_at' => $d->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'demandas' => $demandas,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar as demandas neste momento.');
        }
    }

    /**
     * Retorna contratos de um status específico
     */
    public function getContratosPorStatus(Request $request, int $tabulacaoId)
    {
        try {
            $empresaId = $this->tenantId();

            if (! Tabulacoes::where('empresa_id', $empresaId)->whereKey($tabulacaoId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Etapa não encontrada.',
                ], 404);
            }

            $contratos = DB::table('vendas')
                ->select([
                    'vendas.id',
                    'vendas.numero_proposta',
                    'vendas.nome_contrato',
                    'vendas.operadora',
                    'vendas.nome_plano',
                    'vendas.valor_contrato',
                    'vendas.created_at as data_venda',
                    'vendas.contato_id',
                    'vendas.motivo_pendencia',
                    'users.name as vendedor',
                    'tabulacoes.descricao as status_atual',
                    'vendas.tabulacao_updated_at as status_updated_at',
                ])
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'vendas.user_id')
                        ->on('users.empresa_id', '=', 'vendas.empresa_id')
                        ->where('users.is_platform_admin', false);
                })
                ->leftJoin('tabulacoes', function ($join) {
                    $join->on('tabulacoes.id', '=', 'vendas.tabulacao_id')
                        ->on('tabulacoes.empresa_id', '=', 'vendas.empresa_id');
                })
                ->where('vendas.empresa_id', $empresaId)
                ->where('vendas.tabulacao_id', $tabulacaoId)
                ->orderBy('vendas.tabulacao_updated_at', 'asc')
                ->get();

            $contratosFormatados = $contratos->map(function ($c) {
                $diasNaFila = $c->data_venda
                    ? (int) Carbon::parse($c->data_venda)->diffInDays(now())
                    : 0;

                return [
                    'id' => $c->id,
                    'numero_proposta' => $c->numero_proposta,
                    'nome_contrato' => $c->nome_contrato,
                    'operadora' => $c->operadora,
                    'plano' => $c->nome_plano,
                    'valor' => $c->valor_contrato,
                    'vendedor' => $c->vendedor,
                    'data_venda' => Carbon::parse($c->data_venda)->format('d/m/Y'),
                    'dias_na_fila' => $diasNaFila,
                    'motivo_pendencia' => $c->motivo_pendencia,
                    'contato_id' => $c->contato_id,
                ];
            });

            return response()->json([
                'success' => true,
                'contratos' => $contratosFormatados,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar os contratos neste momento.');
        }
    }

    /**
     * Retorna histórico de uma venda
     */
    public function getHistorico(int $vendaId)
    {
        try {
            $empresaId = $this->tenantId();

            // Verificar se a venda pertence à empresa
            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->when(
                    (int) Auth::user()->user_role_id === UserRole::VENDEDOR,
                    fn ($query) => $query->where('user_id', Auth::id())
                )
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venda não encontrada.',
                ], 404);
            }

            // Buscar histórico
            $historico = VendaHistorico::with([
                'usuario' => fn ($query) => $query->tenantActor($empresaId),
                'tabulacaoAnterior' => fn ($query) => $query->where('tabulacoes.empresa_id', $empresaId),
                'tabulacaoNova' => fn ($query) => $query->where('tabulacoes.empresa_id', $empresaId),
            ])
                ->where('venda_id', $vendaId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'data' => $h->created_at->format('d/m/Y H:i'),
                        'usuario' => $h->usuario->name ?? 'Sistema',
                        'status_anterior' => $h->tabulacaoAnterior->descricao ?? 'N/A',
                        'status_novo' => $h->tabulacaoNova->descricao ?? 'N/A',
                        'status_anterior_codigo' => $h->tabulacaoAnterior?->codigo,
                        'status_novo_codigo' => $h->tabulacaoNova?->codigo,
                        'observacao' => $h->observacao,
                        'motivo_pendencia' => $h->motivo_pendencia,
                        'tempo_formatado' => $h->tempo_formatado,
                    ];
                });

            // Informações da venda
            $infoVenda = [
                'id' => $venda->id,
                'numero_proposta' => $venda->numero_proposta,
                'nome_contrato' => $venda->nome_contrato,
                'operadora' => $venda->operadora,
                'valor_contrato' => $venda->valor_contrato,
                'data_criacao' => Carbon::parse($venda->created_at)->format('d/m/Y H:i'),
                'data_implantacao' => $venda->data_implantacao ? Carbon::parse($venda->data_implantacao)->format('d/m/Y') : null,
            ];

            return response()->json([
                'success' => true,
                'venda' => $infoVenda,
                'historico' => $historico,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar o histórico neste momento.');
        }
    }

    /**
     * Retorna cor para cada status
     */
    private function getCorStatus($codigo)
    {
        $cores = [
            TabulationCode::VENDA => '#696cff',
            TabulationCode::ANALISE_DOCUMENTOS => '#03c3ec',
            TabulationCode::PENDENCIA => '#ff3e1d',
            TabulationCode::REGULARIZADO => '#71dd37',
            TabulationCode::ANALISE_OPERADORA => '#ffab00',
            TabulationCode::CONTRATO_GERADO_AGUARDANDO_ASSINATURA => '#8c57ff',
            TabulationCode::AGUARDANDO_ASSINATURA_DS => '#ffc107',
            TabulationCode::BOLETO_DISPONIVEL => '#20c997',
            TabulationCode::IMPLANTADO => '#198754',
            TabulationCode::ESTORNO => '#dc3545',
            TabulationCode::DECLINADO => '#6c757d',
        ];

        return $cores[$codigo] ?? '#8592a3';
    }

    /**
     * Retorna ícone para cada status
     */
    private function getIconeStatus($codigo)
    {
        $icones = [
            TabulationCode::VENDA => 'ri-shopping-cart-line',
            TabulationCode::ANALISE_DOCUMENTOS => 'ri-file-search-line',
            TabulationCode::PENDENCIA => 'ri-error-warning-line',
            TabulationCode::REGULARIZADO => 'ri-checkbox-circle-line',
            TabulationCode::ANALISE_OPERADORA => 'ri-search-eye-line',
            TabulationCode::CONTRATO_GERADO_AGUARDANDO_ASSINATURA => 'ri-draft-line',
            TabulationCode::AGUARDANDO_ASSINATURA_DS => 'ri-pen-nib-line',
            TabulationCode::BOLETO_DISPONIVEL => 'ri-bank-card-line',
            TabulationCode::IMPLANTADO => 'ri-check-double-line',
            TabulationCode::ESTORNO => 'ri-arrow-go-back-line',
            TabulationCode::DECLINADO => 'ri-close-circle-line',
        ];

        return $icones[$codigo] ?? 'ri-question-line';
    }

    /**
     * Lista os acessos de uma venda
     */
    public function getAcessosEmpresa(int $vendaId)
    {
        try {
            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venda não encontrada.',
                ], 404);
            }

            // Acessos sao gerais por CPF/CNPJ: retorna os acessos de todas as
            // vendas da empresa com o mesmo documento, nao apenas desta proposta.
            $documento = preg_replace('/\D/', '', (string) $venda->cpf_cnpj);

            $vendaIds = $documento !== ''
                ? Vendas::where('empresa_id', $empresaId)
                    ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(cpf_cnpj, ''), '.', ''), '-', ''), '/', ''), ' ', '') = ?", [$documento])
                    ->pluck('id')
                : collect([$venda->id]);

            $acessos = AcessoEmpresa::whereIn('venda_id', $vendaIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($acesso) {
                    return [
                        'id' => $acesso->id,
                        'venda_id' => $acesso->venda_id,
                        'email' => $acesso->email,
                        'senha' => $acesso->senha,
                        'cpf' => $acesso->cpf,
                        'created_at' => $acesso->created_at ? Carbon::parse($acesso->created_at)->format('d/m/Y H:i') : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'acessos' => $acessos,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível buscar os acessos neste momento.',
            ], 500);
        }
    }

    /**
     * Cadastra um novo acesso para a empresa/venda
     */
    public function storeAcessoEmpresa(Request $request)
    {
        try {
            $empresaId = $this->tenantId();

            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'email' => ['required', 'email', 'max:150'],
                'senha' => ['required', 'string', 'max:255'],
                'cpf' => ['nullable', 'string', 'max:14'],
            ]);

            $venda = Vendas::where('id', $validated['venda_id'])
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venda não encontrada ou acesso negado.',
                ], 404);
            }

            $acesso = AcessoEmpresa::create([
                'venda_id' => $venda->id,
                'email' => $validated['email'],
                'senha' => $validated['senha'],
                'cpf' => $validated['cpf'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Acesso cadastrado com sucesso.',
                'acesso' => [
                    'id' => $acesso->id,
                    'email' => $acesso->email,
                    'senha' => $acesso->senha,
                    'cpf' => $acesso->cpf,
                    'created_at' => $acesso->created_at ? Carbon::parse($acesso->created_at)->format('d/m/Y H:i') : null,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível cadastrar o acesso neste momento.',
            ], 500);
        }
    }

    /**
     * Atualiza um acesso existente
     */
    public function updateAcessoEmpresa(Request $request, int $id)
    {
        try {
            $empresaId = $this->tenantId();

            $validated = $request->validate([
                'email' => ['required', 'email', 'max:150'],
                'senha' => ['required', 'string', 'max:255'],
                'cpf' => ['nullable', 'string', 'max:14'],
            ]);

            $acesso = AcessoEmpresa::query()
                ->whereHas('venda', fn ($query) => $query->where('empresa_id', $empresaId))
                ->with('venda')
                ->findOrFail($id);

            $acesso->update([
                'email' => $validated['email'],
                'senha' => $validated['senha'],
                'cpf' => $validated['cpf'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Acesso atualizado com sucesso.',
                'acesso' => [
                    'id' => $acesso->id,
                    'email' => $acesso->email,
                    'senha' => $acesso->senha,
                    'cpf' => $acesso->cpf,
                    'created_at' => $acesso->created_at ? Carbon::parse($acesso->created_at)->format('d/m/Y H:i') : null,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso não encontrado.',
            ], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível atualizar o acesso neste momento.',
            ], 500);
        }
    }

    /**
     * Remove um acesso
     */
    public function deleteAcessoEmpresa(int $id)
    {
        try {
            $empresaId = $this->tenantId();

            $acesso = AcessoEmpresa::query()
                ->whereHas('venda', fn ($query) => $query->where('empresa_id', $empresaId))
                ->with('venda')
                ->findOrFail($id);

            $acesso->delete();

            return response()->json([
                'success' => true,
                'message' => 'Acesso removido com sucesso.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso não encontrado.',
            ], 404);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível remover o acesso neste momento.',
            ], 500);
        }
    }

    /**
     * Exibe a página de Pós-Venda (contratos implantados)
     */
    public function posVenda()
    {
        $empresaId = $this->tenantId();
        $empresa = Empresa::query()->findOrFail($empresaId);
        $podeConfigurar = Auth::user()->isPlatformAdmin()
            || in_array((int) Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true);

        // Buscar operadoras para filtro (apenas de contratos implantados)
        $operadoras = Vendas::select('operadora')
            ->where('empresa_id', $empresaId)
            ->whereNotNull('data_implantacao')
            ->whereNotNull('operadora')
            ->where('operadora', '!=', '')
            ->distinct()
            ->orderBy('operadora')
            ->pluck('operadora');

        // Buscar vendedores para filtro (apenas que têm contratos implantados)
        $vendedores = User::query()->tenantMember($empresaId)
            ->select('users.id', 'users.name')
            ->join('vendas', function ($join) {
                $join->on('vendas.user_id', '=', 'users.id')
                    ->on('vendas.empresa_id', '=', 'users.empresa_id');
            })
            ->where('vendas.empresa_id', $empresaId)
            ->where('vendas.tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO))
            ->whereNotNull('vendas.data_implantacao')
            ->distinct()
            ->orderBy('users.name')
            ->get();

        return view('content.pages.backoffice.pos-venda', compact('empresa', 'operadoras', 'vendedores', 'podeConfigurar'));
    }

    public function updatePosVendaSettings(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user->isPlatformAdmin()
                || in_array((int) $user->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true),
            403,
        );

        $validated = $request->validate([
            'pos_venda_aniversarios_janela_dias' => ['required', 'integer', 'between:1,365'],
        ]);

        Empresa::query()->whereKey($this->tenantId())->update($validated);

        return redirect()->route('backoffice.posVenda')
            ->with('success', 'Janela de aniversários atualizada para esta empresa.');
    }

    /**
     * API: Retorna dados dos contratos implantados para Pós-Venda
     */
    public function getPosVendaData(Request $request)
    {
        try {
            $empresaId = $this->tenantId();
            $hoje = Carbon::now();
            $janelaAniversariosDias = (int) Empresa::query()
                ->whereKey($empresaId)
                ->value('pos_venda_aniversarios_janela_dias');

            // Paginação
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(10, (int) $request->input('per_page', 15)));

            $query = DB::table('vendas')
                ->select([
                    'vendas.id',
                    'vendas.numero_proposta',
                    'vendas.nome_contrato',
                    'vendas.cpf_cnpj',
                    'vendas.operadora',
                    'vendas.nome_plano',
                    'vendas.valor_contrato',
                    'vendas.vidas',
                    'vendas.data_implantacao',
                    'vendas.obs_contrato',
                    'vendas.boas_vindas_enviado_em',
                    'vendas.boas_vindas_enviado_por',
                    'users.name as vendedor',
                    'users_bv.name as boas_vindas_por_nome',
                ])
                ->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'vendas.user_id')
                        ->on('users.empresa_id', '=', 'vendas.empresa_id')
                        ->where('users.is_platform_admin', false);
                })
                ->leftJoin('users as users_bv', function ($join) {
                    $join->on('users_bv.id', '=', 'vendas.boas_vindas_enviado_por')
                        ->where(function ($visibility) {
                            $visibility->whereColumn('users_bv.empresa_id', 'vendas.empresa_id')
                                ->orWhere('users_bv.is_platform_admin', true);
                        });
                })
                ->where('vendas.empresa_id', $empresaId)
                ->where('vendas.tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO))
                ->whereNotNull('vendas.data_implantacao');

            // Aplicar filtros
            if ($request->filled('operadora')) {
                $query->where('vendas.operadora', $request->operadora);
            }
            if ($request->filled('vendedor_id')) {
                $query->where('vendas.user_id', $request->vendedor_id);
            }
            if ($request->filled('mes_aniversario')) {
                $query->whereRaw('MONTH(vendas.data_implantacao) = ?', [$request->mes_aniversario]);
            }
            if ($request->filled('busca')) {
                $busca = $request->busca;
                $query->where(function ($q) use ($busca) {
                    $q->where('vendas.nome_contrato', 'like', "%{$busca}%")
                        ->orWhere('vendas.numero_proposta', 'like', "%{$busca}%")
                        ->orWhere('vendas.cpf_cnpj', 'like', "%{$busca}%");
                });
            }
            if ($request->filled('boas_vindas')) {
                if ($request->boas_vindas === 'pendente') {
                    $query->whereNull('vendas.boas_vindas_enviado_em');
                } elseif ($request->boas_vindas === 'enviado') {
                    $query->whereNotNull('vendas.boas_vindas_enviado_em');
                }
            }

            // Contar total para paginação
            $total = $query->count();

            // Buscar contratos paginados
            $contratos = $query
                ->orderBy('vendas.data_implantacao', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Processar dados
            $resultado = $contratos->map(function ($c) use ($hoje) {
                $dataImpl = Carbon::parse($c->data_implantacao);
                $mesesImplantado = (int) $dataImpl->diffInMonths($hoje);

                // Calcular próximo aniversário
                $proximoAniversario = $dataImpl->copy()->year($hoje->year);
                if ($proximoAniversario->isPast()) {
                    $proximoAniversario->addYear();
                }
                $diasParaAniversario = (int) $hoje->diffInDays($proximoAniversario, false);

                return [
                    'id' => $c->id,
                    'numero_proposta' => $c->numero_proposta,
                    'nome_contrato' => $c->nome_contrato,
                    'cpf_cnpj' => $c->cpf_cnpj,
                    'operadora' => $c->operadora,
                    'nome_plano' => $c->nome_plano,
                    'valor_contrato' => $c->valor_contrato,
                    'vidas' => $c->vidas,
                    'vendedor' => $c->vendedor,
                    'data_implantacao' => $dataImpl->format('d/m/Y'),
                    'meses_implantado' => $mesesImplantado,
                    'proximo_aniversario' => $proximoAniversario->format('d/m'),
                    'dias_para_aniversario' => $diasParaAniversario,
                    'obs_contrato' => $c->obs_contrato,
                    'boas_vindas_enviado_em' => $c->boas_vindas_enviado_em ? Carbon::parse($c->boas_vindas_enviado_em)->format('d/m/Y H:i') : null,
                    'boas_vindas_enviado_por' => $c->boas_vindas_por_nome,
                ];
            });

            // KPIs (baseados no total filtrado, não apenas na página atual)
            // Para KPIs precisos, calculamos separadamente
            $kpiQuery = DB::table('vendas')
                ->where('vendas.empresa_id', $empresaId)
                ->where('vendas.tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO))
                ->whereNotNull('vendas.data_implantacao');

            // Aplicar mesmos filtros para KPIs
            if ($request->filled('operadora')) {
                $kpiQuery->where('vendas.operadora', $request->operadora);
            }
            if ($request->filled('vendedor_id')) {
                $kpiQuery->where('vendas.user_id', $request->vendedor_id);
            }
            if ($request->filled('mes_aniversario')) {
                $kpiQuery->whereRaw('MONTH(vendas.data_implantacao) = ?', [$request->mes_aniversario]);
            }
            if ($request->filled('busca')) {
                $busca = $request->busca;
                $kpiQuery->where(function ($q) use ($busca) {
                    $q->where('vendas.nome_contrato', 'like', "%{$busca}%")
                        ->orWhere('vendas.numero_proposta', 'like', "%{$busca}%")
                        ->orWhere('vendas.cpf_cnpj', 'like', "%{$busca}%");
                });
            }

            $kpiData = $kpiQuery->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(vendas.valor_contrato) as valor_total'),
                DB::raw("SUM(CASE WHEN MONTH(vendas.data_implantacao) = {$hoje->month} THEN 1 ELSE 0 END) as aniversarios_mes"),
                DB::raw('SUM(CASE WHEN vendas.boas_vindas_enviado_em IS NULL THEN 1 ELSE 0 END) as aguardando_boas_vindas'),
            ])->first();

            // Calcular próximos aniversários pela janela da empresa.
            $proximosAniversarios = DB::table('vendas')
                ->where('vendas.empresa_id', $empresaId)
                ->where('vendas.tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO))
                ->whereNotNull('vendas.data_implantacao')
                ->whereRaw("
          DATEDIFF(
            DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(data_implantacao), '-', DAY(data_implantacao))) +
            INTERVAL IF(
              DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(data_implantacao), '-', DAY(data_implantacao))) < CURDATE(),
              1, 0
            ) YEAR,
            CURDATE()
          ) BETWEEN 0 AND ?
        ", [$janelaAniversariosDias])
                ->when($request->filled('operadora'), fn ($q) => $q->where('vendas.operadora', $request->operadora))
                ->when($request->filled('vendedor_id'), fn ($q) => $q->where('vendas.user_id', $request->vendedor_id))
                ->count();

            $totalPages = (int) ceil($total / $perPage);

            return response()->json([
                'success' => true,
                'contratos' => $resultado->values(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_prev' => $page > 1,
                    'has_next' => $page < $totalPages,
                ],
                'kpis' => [
                    'total_implantados' => $kpiData->total ?? 0,
                    'aniversarios_mes' => $kpiData->aniversarios_mes ?? 0,
                    'proximos_aniversarios' => $proximosAniversarios,
                    'aniversarios_janela_dias' => $janelaAniversariosDias,
                    'valor_carteira' => $kpiData->valor_total ?? 0,
                    'aguardando_boas_vindas' => $kpiData->aguardando_boas_vindas ?? 0,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar os dados de pós-venda neste momento.');
        }
    }

    /**
     * Lista anotações pós-venda de um contrato
     */
    public function getAnotacoesPosVenda(int $vendaId)
    {
        try {
            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venda não encontrada.',
                ], 404);
            }

            $anotacoes = PosVendaAnotacao::with([
                'usuario' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
            ])
                ->where('venda_id', $vendaId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'descricao' => $a->descricao,
                    'usuario' => $a->usuario->name ?? 'N/A',
                    'data' => $a->created_at->format('d/m/Y H:i'),
                    'data_relativa' => $a->created_at->diffForHumans(),
                ]);

            return response()->json([
                'success' => true,
                'venda' => [
                    'id' => $venda->id,
                    'nome_contrato' => $venda->nome_contrato,
                    'operadora' => $venda->operadora,
                ],
                'anotacoes' => $anotacoes,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar as anotações neste momento.');
        }
    }

    /**
     * Adiciona nova anotação pós-venda
     */
    public function storeAnotacaoPosVenda(Request $request)
    {
        try {
            $request->validate([
                'venda_id' => [
                    'required',
                    'integer',
                    Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId()),
                ],
                'descricao' => 'required|string|max:2000',
            ]);

            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $request->venda_id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venda não encontrada.',
                ], 404);
            }

            $anotacao = PosVendaAnotacao::create([
                'empresa_id' => $empresaId,
                'venda_id' => $request->venda_id,
                'user_id' => Auth::id(),
                'descricao' => $request->descricao,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Anotação salva com sucesso.',
                'anotacao' => [
                    'id' => $anotacao->id,
                    'descricao' => $anotacao->descricao,
                    'usuario' => Auth::user()->name,
                    'data' => $anotacao->created_at->format('d/m/Y H:i'),
                    'data_relativa' => 'agora',
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível salvar a anotação neste momento.');
        }
    }

    /**
     * Atualiza a data de implantação de um contrato
     */
    public function updateDataImplantacao(Request $request)
    {
        try {
            $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'data_implantacao' => 'required|date',
            ]);

            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $request->venda_id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrato não encontrado.',
                ], 404);
            }

            $venda->data_implantacao = $request->data_implantacao;
            $venda->save();

            return response()->json([
                'success' => true,
                'message' => 'Data de implantação atualizada com sucesso.',
                'data_implantacao' => Carbon::parse($venda->data_implantacao)->format('d/m/Y'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível atualizar a data de implantação neste momento.');
        }
    }

    /**
     * Retorna dados da venda + titulares para o modal de Boas Vindas
     */
    public function getBeneficiariosParaBoasVindas(Request $request, int $vendaId)
    {
        try {
            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->with([
                    'usuarioBoasVindas' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
                ])
                ->select('id', 'nome_contrato', 'operadora', 'operadora_id', 'nome_plano', 'telefone1', 'telefone2', 'email', 'data_implantacao', 'boas_vindas_enviado_em', 'boas_vindas_enviado_por')
                ->first();

            if (! $venda) {
                return response()->json(['success' => false, 'message' => 'Contrato não encontrado.'], 404);
            }

            $titulares = VendaTitular::where('venda_id', $vendaId)
                ->select('id', 'nome', 'telefone', 'telefone2', 'email')
                ->get();

            $dependentes = VendaDependente::where('venda_id', $vendaId)
                ->select('id', 'nome', 'telefone1', 'parentesco')
                ->get();

            $empresa = Empresa::find($empresaId);
            $hasToken = ! empty($empresa?->whatsapp_token);
            $operadora = $venda->operadora_id
                ? Operadora::where('empresa_id', $empresaId)->find($venda->operadora_id)
                : null;

            return response()->json([
                'success' => true,
                'venda' => [
                    'nome_contrato' => $venda->nome_contrato,
                    'operadora' => $venda->operadora,
                    'plano' => $venda->nome_plano,
                    'app_links' => [
                        'ios' => $operadora?->app_ios_url ?? '',
                        'android' => $operadora?->app_android_url ?? '',
                    ],
                    'telefone1' => $venda->telefone1,
                    'telefone2' => $venda->telefone2,
                    'email' => $venda->email,
                    'data_implantacao' => $venda->data_implantacao
                      ? Carbon::parse($venda->data_implantacao)->format('d/m/Y')
                      : null,
                    // Alimenta o aviso de reenvio na modal — quem abre precisa saber
                    // que este contrato já recebeu boas-vindas antes de disparar de novo.
                    'boas_vindas_enviado' => $venda->boas_vindas_enviado_em !== null,
                    'boas_vindas_enviado_em' => $venda->boas_vindas_enviado_em?->format('d/m/Y H:i'),
                    'boas_vindas_enviado_por' => $venda->usuarioBoasVindas?->name,
                ],
                'titulares' => $titulares,
                'dependentes' => $dependentes,
                'has_token' => $hasToken,
                'nome_empresa' => $empresa?->nome_fantasia ?? '',
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar os beneficiários neste momento.');
        }
    }

    /**
     * Marca o Boas Vindas como enviado e, opcionalmente, dispara via WhatsApp
     */
    public function marcarBoasVindas(Request $request)
    {
        try {
            $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                // 'sem_whatsapp' mantido por compatibilidade com a tela antiga de
                // gerenciamento de pós-venda (pos-venda.js), que não envia 'canais'.
                'tipo_envio' => 'required|in:padrao,personalizado,sem_whatsapp',
                'canais' => 'nullable|array',
                'canais.*' => 'in:whatsapp,email',
                'destinatarios' => 'array',
                'destinatarios.*.nome' => 'nullable|string|max:150',
                'destinatarios.*.telefone' => 'required|string|max:30',
                'destinatarios_email' => 'array',
                'destinatarios_email.*.nome' => 'nullable|string|max:150',
                'destinatarios_email.*.email' => 'required|email|max:150',
                // No modo padrão exigimos apenas a lista de beneficiários; os demais
                // campos (código, login/senha do app, portal) são opcionais — o
                // template e a mensagem do WhatsApp renderizam só o que vier preenchido.
                'beneficiarios' => 'required_if:tipo_envio,padrao|array|min:1',
                'beneficiarios.*.nome' => 'nullable|string',
                'beneficiarios.*.codigo' => 'nullable|string',
                'login_app' => 'nullable|string|max:100',
                'senha_app' => 'nullable|string|max:100',
                'acessos_app' => 'nullable|array',
                'acessos_app.*.rotulo' => 'nullable|string|max:120',
                'acessos_app.*.login' => 'nullable|string|max:150',
                'acessos_app.*.senha' => 'nullable|string|max:150',
                'portal_user' => 'nullable|string|max:100',
                'portal_senha' => 'nullable|string|max:100',
                'mensagem_personalizada' => 'required_if:tipo_envio,personalizado|nullable|string',
                'observacao' => 'nullable|string|max:500',
            ]);

            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $request->venda_id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json(['success' => false, 'message' => 'Contrato não encontrado.'], 404);
            }

            // Guardado antes do update: define se este disparo é o primeiro ou um reenvio.
            $jaEnviado = $venda->boas_vindas_enviado_em !== null;

            $tipoEnvio = $request->tipo_envio;

            // Compatibilidade: a tela antiga (pos-venda) não envia 'canais' e usa
            // tipo_envio 'sem_whatsapp' para apenas registrar. Derivamos os canais.
            $canais = $request->input('canais');
            if ($canais === null) {
                $canais = $tipoEnvio === 'sem_whatsapp' ? [] : ['whatsapp'];
            }
            $canais = is_array($canais) ? $canais : [];

            // Conteúdo efetivo (sem_whatsapp legado equivale ao template padrão).
            $tipoEnvio = $tipoEnvio === 'personalizado' ? 'personalizado' : 'padrao';

            $enviarWhatsapp = in_array('whatsapp', $canais, true);
            $enviarEmail = in_array('email', $canais, true);

            $empresa = Empresa::find($empresaId);
            $nomeEmpresa = $empresa?->nome_fantasia ?: 'SalesControl';
            $canaisEnviados = [];

            // -------------------------------------------------- WhatsApp
            if ($enviarWhatsapp) {
                $destinatarios = $request->input('destinatarios', []);

                if (empty($destinatarios)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selecione ao menos um destinatário com telefone para o envio via WhatsApp.',
                    ], 422);
                }

                if (empty($empresa?->whatsapp_token)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token do WhatsApp não configurado. Configure o token nas configurações.',
                    ], 422);
                }

                $mensagem = $tipoEnvio === 'padrao'
                  ? $this->buildMensagemPadraoWhatsapp($request, $nomeEmpresa)
                  : $request->mensagem_personalizada;

                $whatsappService = new WhatsappService();
                $erros = [];

                foreach ($destinatarios as $i => $dest) {
                    if ($i > 0) {
                        sleep(1);
                    }

                    $resultado = $whatsappService->send(
                        $empresa->whatsapp_token,
                        $dest['telefone'],
                        $mensagem,
                        saveOnTicket: true, // abre ticket no painel do Ticketz p/ controlar o acesso do cliente
                    );

                    if (! $resultado['success']) {
                        $erros[] = ($dest['nome'] ?? $dest['telefone']).': '.$resultado['message'];
                    }
                }

                if (! empty($erros) && count($erros) === count($destinatarios)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erro ao enviar WhatsApp: '.implode('; ', $erros),
                    ], 422);
                }

                $canaisEnviados[] = 'WhatsApp';
            }

            // -------------------------------------------------- E-mail (Resend)
            if ($enviarEmail) {
                $destinatariosEmail = $request->input('destinatarios_email', []);

                if (empty($destinatariosEmail)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Informe ao menos um destinatário com e-mail válido para o envio por e-mail.',
                    ], 422);
                }

                $dadosEmail = $this->buildDadosEmailPadrao($request, $nomeEmpresa, (string) $venda->operadora, $tipoEnvio);
                $emailVendedor = trim((string) $venda->user()->tenantMember((int) $venda->empresa_id)->value('email'));
                $erros = [];

                foreach ($destinatariosEmail as $dest) {
                    try {
                        $email = Mail::to($dest['email']);
                        $emailsCopia = collect([
                            $emailVendedor,
                        ])->filter(fn (string $endereco) => $endereco !== '' && strcasecmp($endereco, $dest['email']) !== 0)
                            ->unique(fn (string $endereco) => mb_strtolower($endereco))
                            ->values()
                            ->all();

                        if ($emailsCopia !== []) {
                            $email->cc($emailsCopia);
                        }

                        $email->send(new BoasVindasMail($dadosEmail));
                    } catch (\Throwable $e) {
                        report($e);
                        $erros[] = $dest['nome'] ?? $dest['email'];
                    }
                }

                if (! empty($erros) && count($erros) === count($destinatariosEmail)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Não foi possível enviar o e-mail para: '.implode('; ', $erros).'.',
                    ], 422);
                }

                $canaisEnviados[] = 'E-mail';
            }

            $venda->update([
                'boas_vindas_enviado_em' => now(),
                'boas_vindas_enviado_por' => Auth::id(),
            ]);

            $modoLabel = $tipoEnvio === 'padrao' ? 'padrão' : 'personalizado';

            if (! empty($canaisEnviados)) {
                $partes = [];

                if ($enviarWhatsapp) {
                    $listaWpp = collect($request->input('destinatarios', []))
                        ->map(fn ($d) => ($d['nome'] ?? '').' ('.($d['telefone'] ?? '').')')
                        ->implode(', ');
                    $partes[] = "WhatsApp → {$listaWpp}";
                }

                if ($enviarEmail) {
                    $listaEmail = collect($request->input('destinatarios_email', []))
                        ->map(fn ($d) => ($d['nome'] ?? '').' <'.($d['email'] ?? '').'>')
                        ->implode(', ');
                    $partes[] = "E-mail → {$listaEmail}";
                }

                $canaisStr = implode(' e ', $canaisEnviados);
                $rotulo = $jaEnviado ? 'Boas Vindas REENVIADO' : 'Boas Vindas enviado';
                $descricaoAnotacao = "{$rotulo} via {$canaisStr} (modo {$modoLabel}). ".implode('; ', $partes).'.';
            } else {
                $descricaoAnotacao = $jaEnviado
                  ? 'Boas Vindas registrado novamente (sem envio).'
                  : 'Boas Vindas registrado (sem envio).';
            }

            if ($request->observacao) {
                $descricaoAnotacao .= ' Obs: '.$request->observacao;
            }

            PosVendaAnotacao::create([
                'empresa_id' => $empresaId,
                'venda_id' => $venda->id,
                'user_id' => Auth::id(),
                'descricao' => $descricaoAnotacao,
            ]);

            $verbo = $jaEnviado ? 'reenviado' : 'enviado';

            return response()->json([
                'success' => true,
                'message' => ! empty($canaisEnviados)
                  ? 'Boas Vindas '.$verbo.' via '.implode(' e ', $canaisEnviados).' com sucesso!'
                  : 'Boas Vindas registrado com sucesso!',
                'canais_enviados' => $canaisEnviados,
                'reenvio' => $jaEnviado,
                'boas_vindas_enviado_em' => now()->format('d/m/Y H:i'),
                'boas_vindas_enviado_por' => Auth::user()->name,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível registrar as boas-vindas neste momento.');
        }
    }

    /**
     * Renderiza a prévia do e-mail de Boas Vindas (mesmo Blade do envio real),
     * sem disparar nada. Consumido pela modal para visualização antes do envio.
     */
    public function previewEmailBoasVindas(Request $request)
    {
        try {
            $request->validate([
                'venda_id' => 'nullable|integer',
                'tipo_envio' => 'nullable|in:padrao,personalizado',
                'beneficiarios' => 'nullable|array',
                'beneficiarios.*.nome' => 'nullable|string',
                'beneficiarios.*.codigo' => 'nullable|string',
                'login_app' => 'nullable|string|max:100',
                'senha_app' => 'nullable|string|max:100',
                'acessos_app' => 'nullable|array',
                'acessos_app.*.rotulo' => 'nullable|string|max:120',
                'acessos_app.*.login' => 'nullable|string|max:150',
                'acessos_app.*.senha' => 'nullable|string|max:150',
                'portal_user' => 'nullable|string|max:100',
                'portal_senha' => 'nullable|string|max:100',
                'mensagem_personalizada' => 'nullable|string',
            ]);

            $empresaId = $this->tenantId();
            $empresa = Empresa::find($empresaId);
            $nomeEmpresa = $empresa?->nome_fantasia ?: 'SalesControl';

            $operadora = (string) $request->input('operadora', '');
            if ($operadora === '' && $request->filled('venda_id')) {
                $operadora = (string) (Vendas::where('id', $request->venda_id)
                    ->where('empresa_id', $empresaId)
                    ->value('operadora') ?? '');
            }

            $tipoEnvio = $request->input('tipo_envio') === 'personalizado' ? 'personalizado' : 'padrao';

            $dadosEmail = $this->buildDadosEmailPadrao($request, $nomeEmpresa, $operadora, $tipoEnvio);

            return view('emails.boas-vindas', ['dados' => $dadosEmail]);
        } catch (\Throwable $e) {
            report($e);

            return response('Não foi possível gerar a prévia neste momento.', 500);
        }
    }

    /**
     * Monta o array de dados consumido pelo BoasVindasMail (template HTML).
     * Reaproveita exatamente os mesmos inputs do envio por WhatsApp.
     */
    /**
     * Normaliza os acessos do aplicativo: aceita a lista nova (acessos_app) ou,
     * como fallback, o par único legado (login_app/senha_app). Descarta vazios.
     *
     * @return array<int, array{rotulo:string, login:string, senha:string}>
     */
    private function normalizarAcessosApp(Request $request): array
    {
        $acessos = $request->input('acessos_app', []);
        $acessos = is_array($acessos) ? $acessos : [];

        $acessos = array_values(array_filter(
            $acessos,
            fn ($a) => trim((string) ($a['login'] ?? '')) !== '' || trim((string) ($a['senha'] ?? '')) !== ''
        ));

        if (empty($acessos)) {
            $login = trim((string) $request->input('login_app', ''));
            $senha = trim((string) $request->input('senha_app', ''));
            if ($login !== '' || $senha !== '') {
                $acessos = [['rotulo' => '', 'login' => $login, 'senha' => $senha]];
            }
        }

        return array_map(fn ($a) => [
            'rotulo' => trim((string) ($a['rotulo'] ?? '')),
            'login' => trim((string) ($a['login'] ?? '')),
            'senha' => (string) ($a['senha'] ?? ''),
        ], $acessos);
    }

    private function buildDadosEmailPadrao(Request $request, string $nomeEmpresa, string $operadora, string $tipoEnvio): array
    {
        return [
            'modo' => $tipoEnvio,
            'nomeContrato' => $request->input('nome_contrato', ''),
            'nomeEmpresa' => $nomeEmpresa ?: 'SalesControl',
            'operadora' => $operadora,
            'beneficiarios' => $request->input('beneficiarios', []),
            'acessosApp' => $this->normalizarAcessosApp($request),
            'loginApp' => $request->input('login_app', ''),
            'senhaApp' => $request->input('senha_app', ''),
            'linkIos' => $request->input('link_ios', ''),
            'linkAndroid' => $request->input('link_android', ''),
            'portalUser' => $request->input('portal_user', ''),
            'portalSenha' => $request->input('portal_senha', ''),
            'corpoPersonalizado' => $request->input('mensagem_personalizada', ''),
            'assunto' => 'Boas-vindas à '.($nomeEmpresa ?: 'SalesControl'),
        ];
    }

    /**
     * Monta a mensagem padrão de boas-vindas para WhatsApp
     */
    private function buildMensagemPadraoWhatsapp(Request $request, string $nomeEmpresa): string
    {
        $nomeContrato = $request->input('nome_contrato', '');
        $beneficiarios = $request->input('beneficiarios', []);
        $acessosApp = $this->normalizarAcessosApp($request);
        $linkIos = $request->input('link_ios', '');
        $linkAndroid = $request->input('link_android', '');
        $portalUser = $request->input('portal_user', '');
        $portalSenha = $request->input('portal_senha', '');

        $linhasBeneficiarios = '';
        foreach ($beneficiarios as $b) {
            $nome = strtoupper($b['nome'] ?? '');
            $codigo = $b['codigo'] ?? '';
            $linhasBeneficiarios .= "\n* {$nome} – {$codigo}";
        }

        $msg = "Olá, *{$nomeContrato}* 👋\n\n";
        $msg .= "Sejam muito bem-vindos à *{$nomeEmpresa}*!\n\n";
        $msg .= "É uma satisfação tê-los como nossos clientes. Agradecemos pela confiança e reforçamos que, a partir de agora, vocês contam com o nosso Concierge de Pós-Vendas, um atendimento dedicado para oferecer suporte durante toda a utilização do plano de saúde.\n\n";
        $msg .= "*📋 Beneficiários e Matrículas*{$linhasBeneficiarios}\n\n";

        // Blocos de acesso (dinâmicos) — só entram quando o formulário traz os dados.
        if (! empty($acessosApp)) {
            $msg .= "*📱 Login e Senha — Aplicativo da Operadora:*\n";
            foreach ($acessosApp as $a) {
                $rotulo = strtoupper(trim($a['rotulo'] ?? ''));
                if ($rotulo !== '') {
                    $msg .= "*{$rotulo}*\n";
                }
                $msg .= "• Login: {$a['login']}\n";
                $msg .= "• Senha: {$a['senha']}\n";
            }
            $msg .= "\n";
        }

        if (! empty($linkIos) || ! empty($linkAndroid)) {
            $msg .= "*📲 Download do Aplicativo:*\n";
            if (! empty($linkIos)) {
                $msg .= "• iOS: {$linkIos}\n";
            }
            if (! empty($linkAndroid)) {
                $msg .= "• Android: {$linkAndroid}\n";
            }
            $msg .= "\n";
        }

        if (! empty($portalUser)) {
            $msg .= "*🖥️ Acesso ao Portal Corporativo:*\n";
            $msg .= "• Usuário: {$portalUser}\n";
            $msg .= "• Senha: {$portalSenha}\n\n";
        }

        $msg .= "*💙 Sempre que precisarem de auxílio, nossa equipe estará à disposição para ajudar com:*\n";
        $msg .= "* Agendamento e orientações de utilização do plano;\n";
        $msg .= "* Inclusões, exclusões e alterações cadastrais;\n";
        $msg .= "* Emissão de 2ª via de boletos e carteirinhas;\n";
        $msg .= "* Esclarecimento de dúvidas sobre cobertura, reembolso e rede credenciada;\n";
        $msg .= "* Suporte em solicitações junto à operadora.\n\n";
        $msg .= "Nosso compromisso é proporcionar uma experiência tranquila, ágil e segura durante toda a vigência do plano.\n\n";
        $msg .= "Conte conosco sempre que precisar. Será um prazer atendê-los!\n\n";
        $msg .= "*Equipe {$nomeEmpresa} | Concierge de Pós-Vendas*\n\n";
        $msg .= '🤝 Seu plano de saúde com acompanhamento de quem realmente cuida de você';

        return $msg;
    }

    /**
     * Monta a mensagem de apresentação do canal de suporte (enviada antes do boas-vindas)
     */
    private function buildMensagemApresentacao(string $nomeContrato, string $nomeEmpresa): string
    {
        $primeiroNome = explode(' ', trim($nomeContrato))[0];

        $msg = "Olá, *{$primeiroNome}*! 😊\n\n";
        $msg .= "Aqui é a equipe da *{$nomeEmpresa}*. Este é o nosso WhatsApp oficial de suporte e pós-venda.\n\n";
        $msg .= 'Salve nosso contato para que possa nos encontrar facilmente sempre que precisar. ';
        $msg .= 'Estamos à disposição para qualquer dúvida sobre o seu plano!';

        return $msg;
    }

    /**
     * Salva o token do WhatsApp (Ticketz) da empresa
     */
    public function updateWhatsappToken(Request $request)
    {
        try {
            abort_unless(
                in_array((int) Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true),
                403,
                'Acesso não autorizado.'
            );
            $request->validate([
                'whatsapp_token' => 'required|string|min:10|max:500',
            ]);

            $empresaId = $this->tenantId();
            $empresa = Empresa::query()->findOrFail($empresaId);
            $empresa->whatsapp_token = $request->string('whatsapp_token')->value();
            $empresa->save();

            return response()->json(['success' => true, 'message' => 'Token salvo com sucesso!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Informe um token válido.'], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao salvar token.'], 500);
        }
    }

    /**
     * Retorna informações sobre o token WhatsApp configurado (sem expor o valor completo)
     */
    public function getWhatsappConfig()
    {
        $empresaId = $this->tenantId();
        $empresa = Empresa::find($empresaId);
        $token = $empresa?->whatsapp_token ?? '';

        $preview = '';
        if (! empty($token)) {
            $preview = strlen($token) > 8
              ? str_repeat('*', strlen($token) - 4).substr($token, -4)
              : '****';
        }

        return response()->json([
            'success' => true,
            'has_token' => ! empty($token),
            'token_preview' => $preview,
        ]);
    }

    // =============================================
    // Carteira de Clientes
    // =============================================

    public function carteiraClientes()
    {
        $empresaId = $this->tenantId();

        $operadoras = DB::table('vendas')
            ->where('empresa_id', $empresaId)
            ->whereNotNull('operadora')
            ->where('operadora', '!=', '')
            ->distinct()
            ->orderBy('operadora')
            ->pluck('operadora');

        $backoffices = User::query()->tenantMember($empresaId)
            ->whereIn('user_role_id', [UserRole::BACKOFFICE, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])
            ->where('ativo', 'Y')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('content.pages.backoffice.carteira-clientes', compact('operadoras', 'backoffices'));
    }

    public function getCarteiraClientesData(Request $request)
    {
        if ($request->input('visao', 'recentes') === 'recentes') {
            return $this->getImplantadosRecentesData($request);
        }

        try {
            $empresaId = $this->tenantId();
            $implantadoId = $this->tabulationId(TabulationCode::IMPLANTADO);
            $estornoId = $this->tabulationId(TabulationCode::ESTORNO);
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(10, (int) $request->input('per_page', 15)));

            $query = DB::table('vendas as v')
                ->where('v.empresa_id', $empresaId)
                ->whereNotNull('v.cpf_cnpj')
                ->where('v.cpf_cnpj', '!=', '')
                ->groupBy('v.cpf_cnpj')
                ->select([
                    'v.cpf_cnpj',
                    DB::raw('MAX(v.nome_contrato) as nome_contrato'),
                    DB::raw('COUNT(v.id) as total_contratos'),
                    DB::raw('MIN(v.data_implantacao) as primeiro_contrato'),
                    DB::raw('MAX(v.data_implantacao) as ultimo_contrato'),
                    DB::raw('GROUP_CONCAT(DISTINCT v.operadora ORDER BY v.operadora SEPARATOR \', \') as operadoras'),
                ])
                ->selectRaw('SUM(CASE WHEN v.tabulacao_id = ? THEN 1 ELSE 0 END) as contratos_ativos', [$implantadoId])
                ->selectRaw('SUM(CASE WHEN v.tabulacao_id = ? THEN 1 ELSE 0 END) as contratos_cancelados', [$estornoId])
                ->selectRaw('SUM(CASE WHEN v.tabulacao_id = ? THEN v.valor_contrato ELSE 0 END) as valor_ativo', [$implantadoId])
                ->selectRaw('SUM(CASE WHEN v.tabulacao_id = ? THEN COALESCE(v.vidas,0) ELSE 0 END) as vidas_ativas', [$implantadoId]);

            // Filtros
            if ($request->filled('busca')) {
                $busca = $request->busca;
                $query->where(function ($q) use ($busca) {
                    $q->where('v.cpf_cnpj', 'like', "%{$busca}%")
                        ->orWhere('v.nome_contrato', 'like', "%{$busca}%");
                });
            }
            if ($request->filled('operadora')) {
                $query->where('v.operadora', $request->operadora);
            }

            // Filtro de status aplicado via HAVING
            if ($request->filled('status')) {
                match ($request->status) {
                    'ativo' => $query->havingRaw('contratos_ativos > 0 AND contratos_cancelados = 0'),
                    'misto' => $query->havingRaw('contratos_ativos > 0 AND contratos_cancelados > 0'),
                    'inativo' => $query->havingRaw('contratos_ativos = 0'),
                    default => null,
                };
            }

            $total = DB::query()->fromSub(clone $query, 'sub')->count();
            $offset = ($page - 1) * $perPage;
            $results = $query->orderByRaw('primeiro_contrato DESC')
                ->skip($offset)->take($perPage)->get();

            $clientes = $results->map(function ($c) {
                $meses = $c->primeiro_contrato
                  ? Carbon::parse($c->primeiro_contrato)->diffInMonths(now())
                  : 0;

                $anos = intdiv($meses, 12);
                $mesesRest = $meses % 12;
                $tempoLabel = $anos > 0
                  ? "{$anos} ano".($anos > 1 ? 's' : '').($mesesRest > 0 ? " {$mesesRest} m" : '')
                  : "{$meses} ".($meses === 1 ? 'mês' : 'meses');

                $status = match (true) {
                    $c->contratos_ativos > 0 && $c->contratos_cancelados == 0 => 'ativo',
                    $c->contratos_ativos > 0 && $c->contratos_cancelados > 0 => 'misto',
                    default => 'inativo',
                };

                return [
                    'cpf_cnpj' => $c->cpf_cnpj,
                    'cpf_cnpj_formatado' => $this->formatarCpfCnpj($c->cpf_cnpj),
                    'nome_contrato' => $c->nome_contrato,
                    'total_contratos' => (int) $c->total_contratos,
                    'contratos_ativos' => (int) $c->contratos_ativos,
                    'contratos_cancelados' => (int) $c->contratos_cancelados,
                    'valor_ativo' => (float) $c->valor_ativo,
                    'vidas_ativas' => (int) $c->vidas_ativas,
                    'primeiro_contrato' => $c->primeiro_contrato
                      ? Carbon::parse($c->primeiro_contrato)->format('d/m/Y')
                      : null,
                    'meses_cliente' => $meses,
                    'tempo_label' => $tempoLabel,
                    'operadoras' => $c->operadoras,
                    'status' => $status,
                ];
            });

            // KPIs — mesma query base sem paginação
            $kpiSubquery = DB::table('vendas as v2')
                ->where('v2.empresa_id', $empresaId)
                ->whereNotNull('v2.cpf_cnpj')
                ->where('v2.cpf_cnpj', '!=', '')
                ->groupBy('v2.cpf_cnpj')
                ->selectRaw('SUM(CASE WHEN v2.tabulacao_id = ? THEN 1 ELSE 0 END) as ca', [$implantadoId])
                ->selectRaw('SUM(CASE WHEN v2.tabulacao_id = ? THEN 1 ELSE 0 END) as cc2', [$estornoId])
                ->selectRaw('SUM(CASE WHEN v2.tabulacao_id = ? THEN v2.valor_contrato ELSE 0 END) as va', [$implantadoId])
                ->selectRaw('SUM(CASE WHEN v2.tabulacao_id = ? THEN COALESCE(v2.vidas,0) ELSE 0 END) as vi', [$implantadoId]);

            $kpiBase = DB::query()->fromSub($kpiSubquery, 'kpi_sub')->select([
                DB::raw('COUNT(*) as total_clientes'),
                DB::raw('SUM(CASE WHEN ca > 0 THEN 1 ELSE 0 END) as clientes_ativos'),
                DB::raw('SUM(CASE WHEN ca = 0 THEN 1 ELSE 0 END) as clientes_inativos'),
                DB::raw('SUM(va) as valor_carteira'),
                DB::raw('SUM(vi) as total_vidas'),
            ])->first();

            return response()->json([
                'success' => true,
                'clientes' => $clientes,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => max(1, (int) ceil($total / $perPage)),
                    'has_prev' => $page > 1,
                    'has_next' => $page < ceil($total / $perPage),
                ],
                'kpis' => [
                    'total_clientes' => (int) ($kpiBase->total_clientes ?? 0),
                    'clientes_ativos' => (int) ($kpiBase->clientes_ativos ?? 0),
                    'clientes_inativos' => (int) ($kpiBase->clientes_inativos ?? 0),
                    'valor_carteira' => (float) ($kpiBase->valor_carteira ?? 0),
                    'total_vidas' => (int) ($kpiBase->total_vidas ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar a carteira neste momento.');
        }
    }

    private function getImplantadosRecentesData(Request $request)
    {
        try {
            $empresaId = (int) $this->tenantId();
            $page = max(1, (int) $request->input('page', 1));
            $perPage = min(100, max(10, (int) $request->input('per_page', 15)));
            $periodo = in_array((int) $request->input('periodo', 30), [30, 60, 365], true)
                ? (int) $request->input('periodo', 30)
                : 30;
            $dataInicial = now()->startOfDay()->subDays($periodo - 1);
            $tiposCancelamento = [
                TipoDemandaContrato::CANCELAMENTO->value,
                TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value,
                TipoDemandaContrato::CANCELAMENTO_INTERMEDIADORA->value,
                TipoDemandaContrato::CANCELAMENTO_LIMITAR->value,
                TipoDemandaContrato::CARTA_PERMANENCIA->value,
            ];

            $baseQuery = DB::table('vendas as v')
                ->leftJoin('users as bo', function ($join) {
                    $join->on('bo.id', '=', 'v.backoffice_id')
                        ->on('bo.empresa_id', '=', 'v.empresa_id')
                        ->where('bo.is_platform_admin', false);
                })
                ->leftJoin('users as vendedor', function ($join) {
                    $join->on('vendedor.id', '=', 'v.user_id')
                        ->on('vendedor.empresa_id', '=', 'v.empresa_id')
                        ->where('vendedor.is_platform_admin', false);
                })
                ->where('v.empresa_id', $empresaId)
                ->where('v.tabulacao_id', $this->tabulationId(TabulationCode::IMPLANTADO))
                ->whereNotNull('v.data_implantacao')
                ->whereDate('v.data_implantacao', '>=', $dataInicial->toDateString());

            if ($request->filled('busca')) {
                $busca = trim((string) $request->input('busca'));
                $baseQuery->where(function ($query) use ($busca) {
                    $query->where('v.cpf_cnpj', 'like', "%{$busca}%")
                        ->orWhere('v.nome_contrato', 'like', "%{$busca}%")
                        ->orWhere('v.numero_proposta', 'like', "%{$busca}%");
                });
            }

            if ($request->filled('operadora')) {
                $baseQuery->where('v.operadora', $request->input('operadora'));
            }

            if ($request->filled('backoffice')) {
                if ($request->input('backoffice') === 'sem_responsavel') {
                    $baseQuery->whereNull('v.backoffice_id');
                } else {
                    $baseQuery->where('v.backoffice_id', (int) $request->input('backoffice'));
                }
            }

            $portabilidadePendente = function ($query) {
                $query->selectRaw('1')
                    ->from('vendas_portabilidades as vp')
                    ->whereColumn('vp.venda_id', 'v.id')
                    ->where('vp.status', 'PENDENTE');
            };
            $cancelamentoPendente = function ($query) use ($tiposCancelamento) {
                $query->selectRaw('1')
                    ->from('venda_demandas as vd')
                    ->whereColumn('vd.venda_id', 'v.id')
                    ->whereColumn('vd.empresa_id', 'v.empresa_id')
                    ->where('vd.status', 'PENDENTE')
                    ->whereIn('vd.tipo', $tiposCancelamento);
            };

            $kpiBase = clone $baseQuery;
            $kpis = [
                'implantados' => (clone $kpiBase)->count('v.id'),
                'atencao' => (clone $kpiBase)
                    ->where(function ($query) use ($portabilidadePendente, $cancelamentoPendente) {
                        $query->whereExists($portabilidadePendente)
                            ->orWhereExists($cancelamentoPendente);
                    })
                    ->count('v.id'),
                'portabilidades' => (clone $kpiBase)->whereExists($portabilidadePendente)->count('v.id'),
                'cancelamentos' => (clone $kpiBase)->whereExists($cancelamentoPendente)->count('v.id'),
                'total_vidas' => (int) ((clone $kpiBase)->sum('v.vidas') ?? 0),
                'valor_implantado' => (float) ((clone $kpiBase)->sum('v.valor_contrato') ?? 0),
            ];

            $distribuicaoBackoffice = (clone $kpiBase)
                ->selectRaw("COALESCE(bo.name, 'Sem responsável') as responsavel, v.backoffice_id, COUNT(v.id) as total")
                ->groupBy('v.backoffice_id', 'bo.name')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->backoffice_id ? (string) $item->backoffice_id : 'sem_responsavel',
                    'nome' => $item->responsavel,
                    'total' => (int) $item->total,
                ]);

            if ($request->filled('acao')) {
                match ($request->input('acao')) {
                    'portabilidade' => $baseQuery->whereExists($portabilidadePendente),
                    'cancelamento' => $baseQuery->whereExists($cancelamentoPendente),
                    'sem_acao' => $baseQuery
                        ->whereNotExists($portabilidadePendente)
                        ->whereNotExists($cancelamentoPendente),
                    default => null,
                };
            }

            $total = (clone $baseQuery)->count('v.id');
            $results = $baseQuery
                ->select([
                    'v.id', 'v.numero_proposta', 'v.nome_contrato', 'v.cpf_cnpj',
                    'v.operadora', 'v.nome_plano', 'v.valor_contrato', 'v.vidas',
                    'v.data_implantacao', 'v.data_vigencia', 'v.boas_vindas_enviado_em',
                    'v.backoffice_id', 'bo.name as backoffice', 'vendedor.name as vendedor',
                ])
                ->selectSub(function ($query) {
                    $query->from('vendas_portabilidades as vp')
                        ->whereColumn('vp.venda_id', 'v.id')
                        ->where('vp.status', 'PENDENTE')
                        ->selectRaw('COUNT(*)');
                }, 'portabilidades_pendentes')
                ->selectSub(function ($query) use ($tiposCancelamento) {
                    $query->from('venda_demandas as vd')
                        ->whereColumn('vd.venda_id', 'v.id')
                        ->whereColumn('vd.empresa_id', 'v.empresa_id')
                        ->where('vd.status', 'PENDENTE')
                        ->whereIn('vd.tipo', $tiposCancelamento)
                        ->selectRaw('COUNT(*)');
                }, 'cancelamentos_pendentes')
                ->orderByDesc('v.data_implantacao')
                ->orderByDesc('v.id')
                ->forPage($page, $perPage)
                ->get();

            $contratos = $results->map(function ($contrato) {
                $implantacao = Carbon::parse($contrato->data_implantacao)->startOfDay();
                $diasImplantado = (int) $implantacao->diffInDays(now()->startOfDay());
                $portabilidades = (int) $contrato->portabilidades_pendentes;
                $cancelamentos = (int) $contrato->cancelamentos_pendentes;

                return [
                    'id' => (int) $contrato->id,
                    'numero_proposta' => $contrato->numero_proposta,
                    'nome_contrato' => $contrato->nome_contrato,
                    'cpf_cnpj' => $contrato->cpf_cnpj,
                    'cpf_cnpj_formatado' => $this->formatarCpfCnpj($contrato->cpf_cnpj),
                    'operadora' => $contrato->operadora,
                    'nome_plano' => $contrato->nome_plano,
                    'valor_contrato' => (float) $contrato->valor_contrato,
                    'vidas' => (int) $contrato->vidas,
                    'data_implantacao' => $implantacao->format('d/m/Y'),
                    'data_vigencia' => $contrato->data_vigencia
                        ? Carbon::parse($contrato->data_vigencia)->format('d/m/Y')
                        : null,
                    'dias_implantado' => $diasImplantado,
                    'idade_label' => match ($diasImplantado) {
                        0 => 'Implantado hoje',
                        1 => 'Implantado ontem',
                        default => "Implantado há {$diasImplantado} dias",
                    },
                    'backoffice_id' => $contrato->backoffice_id,
                    'backoffice' => $contrato->backoffice ?: 'Sem responsável',
                    'vendedor' => $contrato->vendedor,
                    'portabilidades_pendentes' => $portabilidades,
                    'cancelamentos_pendentes' => $cancelamentos,
                    'precisa_atencao' => $portabilidades > 0 || $cancelamentos > 0,
                    'boas_vindas' => ! empty($contrato->boas_vindas_enviado_em),
                ];
            });

            $totalPages = max(1, (int) ceil($total / $perPage));

            return response()->json([
                'success' => true,
                'visao' => 'recentes',
                'contratos' => $contratos,
                'distribuicao_backoffice' => $distribuicaoBackoffice,
                'periodo' => $periodo,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_prev' => $page > 1,
                    'has_next' => $page < $totalPages,
                ],
                'kpis' => $kpis,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar os implantados recentes neste momento.');
        }
    }

    public function getDetalheClienteCarteira(Request $request, string $cnpj)
    {
        try {
            $empresaId = $this->tenantId();
            $implantadoId = $this->tabulationId(TabulationCode::IMPLANTADO);

            $contratos = DB::table('vendas as v')
                ->leftJoin('tabulacoes as t', function ($join) {
                    $join->on('t.id', '=', 'v.tabulacao_id')
                        ->on('t.empresa_id', '=', 'v.empresa_id');
                })
                ->leftJoin('users as u', function ($join) {
                    $join->on('u.id', '=', 'v.user_id')
                        ->on('u.empresa_id', '=', 'v.empresa_id')
                        ->where('u.is_platform_admin', false);
                })
                ->where('v.empresa_id', $empresaId)
                ->where('v.cpf_cnpj', $cnpj)
                ->orderByDesc('v.data_implantacao')
                ->select([
                    'v.id', 'v.numero_proposta', 'v.nome_contrato',
                    'v.operadora', 'v.nome_plano', 'v.valor_contrato',
                    'v.vidas', 'v.data_implantacao', 'v.data_vigencia',
                    'v.boas_vindas_enviado_em',
                    'v.tipo_empresa',
                    't.descricao as status_descricao', 'v.tabulacao_id',
                    'u.name as vendedor',
                ])
                ->get()
                ->map(function ($c, $idx) use ($implantadoId) {
                    return [
                        'id' => $c->id,
                        'numero_proposta' => $c->numero_proposta,
                        'nome_contrato' => $c->nome_contrato,
                        'operadora' => $c->operadora,
                        'nome_plano' => $c->nome_plano,
                        'valor_contrato' => (float) $c->valor_contrato,
                        'vidas' => (int) $c->vidas,
                        'data_implantacao' => $c->data_implantacao
                          ? Carbon::parse($c->data_implantacao)->format('d/m/Y')
                          : null,
                        'data_vigencia' => $c->data_vigencia
                          ? Carbon::parse($c->data_vigencia)->format('d/m/Y')
                          : null,
                        'boas_vindas' => ! empty($c->boas_vindas_enviado_em),
                        'status_descricao' => $c->status_descricao ?? 'Sem status',
                        'tabulacao_id' => $c->tabulacao_id,
                        'is_ativo' => (int) $c->tabulacao_id === $implantadoId,
                        'vendedor' => $c->vendedor,
                        'primeiro' => $idx === 0, // mais recente
                    ];
                });

            return response()->json(['success' => true, 'contratos' => $contratos]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar os contratos do cliente neste momento.');
        }
    }

    private function formatarCpfCnpj(string $valor): string
    {
        $v = preg_replace('/\D/', '', $valor);
        if (strlen($v) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $v);
        }
        if (strlen($v) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $v);
        }

        return $valor;
    }

    // =============================================
    // Demandas de Contrato
    // =============================================

    public function getDemandasContrato(int $vendaId)
    {
        try {
            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrato não encontrado.',
                ], 404);
            }

            $demandas = VendaDemanda::with([
                'criador' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
                'concluidaPor' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
            ])
                ->where('venda_id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->orderByRaw("FIELD(status, 'PENDENTE', 'CONCLUIDA')")
                ->orderBy('created_at', 'desc')
                ->get();

            $pendentes = $demandas->where('status', 'PENDENTE')->count();
            $total = $demandas->count();

            return response()->json([
                'success' => true,
                'demandas' => $demandas,
                'pendentes' => $pendentes,
                'total' => $total,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível carregar as demandas do contrato neste momento.');
        }
    }

    public function storeDemandaContrato(Request $request)
    {
        try {
            $validated = $request->validate([
                'venda_id' => ['required', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->tenantId())],
                'tipo' => 'required|string|max:50',
                'titulo' => 'required|string|max:255',
                'descricao' => 'nullable|string|max:1000',
            ]);

            $empresaId = $this->tenantId();

            $venda = Vendas::where('id', $validated['venda_id'])
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contrato não encontrado.',
                ], 404);
            }

            $demanda = VendaDemanda::create([
                'venda_id' => $venda->id,
                'empresa_id' => $empresaId,
                'created_by' => Auth::id(),
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'],
                'descricao' => $validated['descricao'] ?? null,
                'status' => 'PENDENTE',
            ]);

            $demanda->load([
                'criador' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
                'concluidaPor' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demanda criada com sucesso.',
                'demanda' => $demanda,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível criar a demanda neste momento.');
        }
    }

    public function updateDemandaContrato(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'tipo' => 'required|string|max:50',
                'titulo' => 'required|string|max:255',
                'descricao' => 'nullable|string|max:1000',
            ]);

            $empresaId = $this->tenantId();

            $demanda = VendaDemanda::where('id', $id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $demanda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demanda não encontrada.',
                ], 404);
            }

            $demanda->update([
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'],
                'descricao' => $validated['descricao'] ?? null,
            ]);

            $demanda->load([
                'criador' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
                'concluidaPor' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demanda atualizada com sucesso.',
                'demanda' => $demanda,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível atualizar a demanda neste momento.');
        }
    }

    public function toggleStatusDemandaContrato(int $id)
    {
        try {
            $empresaId = $this->tenantId();

            $demanda = VendaDemanda::where('id', $id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $demanda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demanda não encontrada.',
                ], 404);
            }

            if ($demanda->status === 'PENDENTE') {
                $demanda->update([
                    'status' => 'CONCLUIDA',
                    'concluida_por' => Auth::id(),
                    'concluida_em' => now(),
                ]);
            } else {
                $demanda->update([
                    'status' => 'PENDENTE',
                    'concluida_por' => null,
                    'concluida_em' => null,
                ]);
            }

            $demanda->load([
                'criador' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
                'concluidaPor' => fn ($query) => $query->select('id', 'name')->tenantActor($empresaId),
            ]);

            return response()->json([
                'success' => true,
                'message' => $demanda->status === 'CONCLUIDA' ? 'Demanda concluída!' : 'Demanda reaberta.',
                'demanda' => $demanda,
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível alterar o status da demanda neste momento.');
        }
    }

    public function destroyDemandaContrato(int $id)
    {
        try {
            $empresaId = $this->tenantId();

            $demanda = VendaDemanda::where('id', $id)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $demanda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demanda não encontrada.',
                ], 404);
            }

            $demanda->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demanda removida com sucesso.',
            ]);
        } catch (\Throwable $e) {
            return $this->internalError($e, 'Não foi possível remover a demanda neste momento.');
        }
    }

    // ==================== FAQs ====================

    public function faqs()
    {
        $operadoras = Operadora::where('empresa_id', $this->tenantId())
            ->where('status', 'Y')
            ->orderBy('nome')
            ->get();

        return view('content.pages.backoffice.faqs', [
            'operadoras' => $operadoras,
        ]);
    }

    private function regrasUrlHttpsOpcional(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
            if ($value === null || $value === '') {
                return;
            }

            if (! str_starts_with($value, 'https://') || ! filter_var($value, FILTER_VALIDATE_URL)) {
                $fail('Informe uma URL HTTPS válida.');
            }
        }];
    }

    public function getFaqs(Request $request)
    {
        $empresaId = (int) $this->tenantId();
        $validated = $request->validate([
            'operadora_id' => [
                'nullable',
                'integer',
                Rule::exists('operadoras', 'id')->where('empresa_id', $empresaId),
            ],
        ]);

        $query = Faq::with('operadora')
            ->where('empresa_id', $empresaId)
            ->orderBy('ordem')
            ->orderBy('created_at', 'desc');

        if (isset($validated['operadora_id'])) {
            $query->where('operadora_id', (int) $validated['operadora_id']);
        }

        return response()->json($query->get());
    }

    public function createFaq(Request $request)
    {
        if (! in_array(Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::SUPERVISOR])) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $empresaId = (int) $this->tenantId();
        $validated = $request->validate([
            'operadora_id' => [
                'required',
                'integer',
                Rule::exists('operadoras', 'id')->where('empresa_id', $empresaId),
            ],
            'titulo' => 'required|string|max:255',
            'resposta' => 'required|string',
            'status' => 'nullable|in:Y,N',
            'ordem' => 'nullable|integer|min:0',
        ]);

        try {
            Faq::create([
                'empresa_id' => $empresaId,
                'operadora_id' => (int) $validated['operadora_id'],
                'titulo' => $validated['titulo'],
                'resposta' => $validated['resposta'],
                'status' => $validated['status'] ?? 'Y',
                'ordem' => $validated['ordem'] ?? 0,
            ]);

            return response()->json(['success' => true, 'message' => 'FAQ cadastrado com sucesso!'], 201);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Erro ao cadastrar FAQ.'], 500);
        }
    }

    public function updateFaq(Request $request, $id)
    {
        if (! in_array(Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::SUPERVISOR])) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $empresaId = (int) $this->tenantId();
        $validated = $request->validate([
            'operadora_id' => [
                'required',
                'integer',
                Rule::exists('operadoras', 'id')->where('empresa_id', $empresaId),
            ],
            'titulo' => 'required|string|max:255',
            'resposta' => 'required|string',
            'status' => 'nullable|in:Y,N',
            'ordem' => 'nullable|integer|min:0',
        ]);

        $faq = Faq::where('empresa_id', $empresaId)->findOrFail($id);

        try {
            $faq->update([
                'operadora_id' => (int) $validated['operadora_id'],
                'titulo' => $validated['titulo'],
                'resposta' => $validated['resposta'],
                'status' => $validated['status'] ?? 'Y',
                'ordem' => $validated['ordem'] ?? 0,
            ]);

            return response()->json(['success' => true, 'message' => 'FAQ atualizado com sucesso!']);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Erro ao atualizar FAQ.'], 500);
        }
    }

    public function deleteFaq(Request $request, $id)
    {
        if (! in_array(Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::SUPERVISOR])) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $faq = Faq::where('empresa_id', $this->tenantId())->findOrFail($id);

        try {
            $faq->delete();

            return response()->json(['success' => true, 'message' => 'FAQ excluído com sucesso!']);
        } catch (\Exception $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Erro ao excluir FAQ.'], 500);
        }
    }
}
