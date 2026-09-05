<?php

namespace App\Http\Controllers\pages\whatsapp;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ComentariosRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\WhatsappConversaRepositoryInterface;
use App\Support\TenantContext;
use App\UseCases\WhatsappConversaUseCase;
use App\UseCases\WhatsappMensagemUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WhatsappChatController extends Controller
{
    public function __construct(
        private WhatsappConversaUseCase $conversaUseCase,
        private WhatsappMensagemUseCase $mensagemUseCase,
        private WhatsappConversaRepositoryInterface $conversaRepository,
        private ComentariosRepositoryInterface $comentariosRepository,
        private ContatosCorretoresRepositoryInterface $contatosCorretoresRepository
    ) {}

    public function index(?int $conversaId = null)
    {
        $user = Auth::user();

        return view('content.pages.whatsapp.chat', [
            'conversaInicialId' => $conversaId,
            'podeEnviar' => $user->isPlatformAdmin()
                || (int) $user->user_role_id === \App\Enums\UserRole::VENDEDOR,
        ]);
    }

    public function getConversas(Request $request): JsonResponse
    {
        $user = Auth::user();

        $modo = in_array($request->query('modo'), ['ativas', 'carteira', 'arquivadas'], true)
            ? $request->query('modo')
            : 'ativas';

        $conversas = $this->conversaRepository->getConversasLista(
            $this->tenantId(),
            $this->conversaUseCase->escopoUsuario($user),
            $request->query('busca'),
            $modo
        );

        return response()->json([
            'success' => true,
            'data' => $conversas->map(fn ($conversa) => $this->serializarConversa($conversa)),
        ]);
    }

    public function getMensagens(int $conversaId, Request $request): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa) {
            return response()->json(['success' => false, 'message' => 'Conversa não encontrada.'], 404);
        }

        // Backfill da foto de perfil (conversas antigas / foto expirada) — no máximo 1x/hora
        if (! $conversa->foto_url && \Illuminate\Support\Facades\Cache::add("wa-foto-{$conversa->id}", 1, 3600)) {
            \App\Jobs\Whatsapp\AtualizarFotoPerfilConversa::dispatch($conversa->id);
        }

        $mensagens = $this->mensagemUseCase->getThread(
            $conversa->id,
            $request->query('before_id') ? (int) $request->query('before_id') : null
        );

        return response()->json([
            'success' => true,
            'data' => [
                'conversa' => $this->serializarConversa($conversa, true),
                'mensagens' => $mensagens,
            ],
        ]);
    }

    public function enviarMensagem(int $conversaId, Request $request): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa) {
            return response()->json(['success' => false, 'message' => 'Conversa não encontrada.'], 404);
        }

        if (! $this->conversaUseCase->podeInteragir(Auth::user(), $conversa)) {
            return response()->json(['success' => false, 'message' => 'Sem permissão para enviar nesta conversa.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'texto' => 'required_without:arquivo|nullable|string|max:60000',
            'arquivo' => 'required_without:texto|nullable|file|max:20480',
            'tipo' => 'nullable|in:ptt',
            'quoted_message_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('arquivo')) {
            $mensagem = $this->mensagemUseCase->enviarMidia(
                $conversa,
                $request->file('arquivo'),
                $request->input('texto'),
                $request->input('tipo')
            );
        } else {
            $mensagem = $this->mensagemUseCase->enviarTexto(
                $conversa,
                (string) $request->input('texto'),
                $request->input('quoted_message_id')
            );
        }

        return response()->json(['success' => true, 'data' => $mensagem]);
    }

    public function reenviarMensagem(int $conversaId, int $mensagemId): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa || ! $this->conversaUseCase->podeInteragir(Auth::user(), $conversa)) {
            return response()->json(['success' => false], 403);
        }

        $mensagem = $this->mensagemUseCase->reenviar($conversa, $mensagemId);

        return response()->json(['success' => (bool) $mensagem, 'data' => $mensagem]);
    }

    public function marcarLida(int $conversaId): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa) {
            return response()->json(['success' => false], 404);
        }

        $this->mensagemUseCase->marcarComoLida($conversa);

        return response()->json(['success' => true]);
    }

    public function vincularContato(int $conversaId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contato_id' => ['nullable', 'integer', Rule::exists('contatos', 'id')->where('empresa_id', app(TenantContext::class)->id())],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $vinculado = $this->conversaUseCase->vincularContato(
            Auth::user(),
            $conversaId,
            $request->input('contato_id') ? (int) $request->input('contato_id') : null
        );

        if (! $vinculado) {
            return response()->json(['success' => false, 'message' => 'Não foi possível vincular: o lead precisa estar atribuído ao vendedor da conversa.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Leads do vendedor para o modal de vínculo manual.
     */
    public function buscarLeads(Request $request): JsonResponse
    {
        $user = Auth::user();
        $busca = (string) $request->query('busca', '');
        $digitos = preg_replace('/\D/', '', $busca);

        $leads = DB::table('contatos as c')
            ->join('contatos_corretores as cc', function ($join) {
                $join->on('cc.contato_id', '=', 'c.id')
                    ->on('cc.empresa_id', '=', 'c.empresa_id');
            })
            ->where('c.empresa_id', $this->tenantId())
            ->where('cc.empresa_id', $this->tenantId())
            ->where('cc.user_id', $user->id)
            ->when($busca !== '', function ($q) use ($busca, $digitos) {
                $q->where(function ($sub) use ($busca, $digitos) {
                    $sub->where('c.nome_cliente', 'LIKE', "%{$busca}%");
                    if ($digitos !== '') {
                        $sub->orWhere('c.telefone1', 'LIKE', "%{$digitos}%")
                            ->orWhere('c.telefone2', 'LIKE', "%{$digitos}%")
                            ->orWhere('c.telefone3', 'LIKE', "%{$digitos}%");
                    }
                });
            })
            ->orderByDesc('cc.id')
            ->limit(20)
            ->get(['c.id', 'c.nome_cliente', 'c.telefone1', 'c.plano', 'c.categoria']);

        return response()->json(['success' => true, 'data' => $leads]);
    }

    public function novaConversa(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero' => 'required|string|max:20',
            'nome' => 'nullable|string|max:255',
            'criar_lead' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $resultado = $this->conversaUseCase->novaConversa(
            Auth::user(),
            (string) $request->input('numero'),
            $request->input('nome'),
            (bool) $request->boolean('criar_lead', true)
        );

        if (isset($resultado['erro'])) {
            return response()->json(['success' => false, 'message' => $resultado['erro']], 422);
        }

        return response()->json(['success' => true, 'data' => ['conversa_id' => $resultado['conversa_id']]]);
    }

    /**
     * Descarta a conversa do funil (ex: conversa pessoal) — some do kanban
     * e da lista principal, mas fica visível na aba "Descartadas".
     */
    public function descartarConversa(int $conversaId): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa || ! $this->conversaUseCase->podeInteragir(Auth::user(), $conversa)) {
            return response()->json(['success' => false], 403);
        }

        $this->conversaRepository->setArquivada($conversa->id, (int) $conversa->empresa_id, true);

        return response()->json(['success' => true]);
    }

    public function restaurarConversa(int $conversaId): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa || ! $this->conversaUseCase->podeInteragir(Auth::user(), $conversa)) {
            return response()->json(['success' => false], 403);
        }

        $this->conversaRepository->setArquivada($conversa->id, (int) $conversa->empresa_id, false);

        return response()->json(['success' => true]);
    }

    public function limparConversa(int $conversaId): JsonResponse
    {
        $ok = $this->conversaUseCase->limparConversa(Auth::user(), $conversaId);

        return response()->json(['success' => $ok], $ok ? 200 : 403);
    }

    public function apagarConversa(int $conversaId): JsonResponse
    {
        $ok = $this->conversaUseCase->apagarConversa(Auth::user(), $conversaId);

        return response()->json(['success' => $ok], $ok ? 200 : 403);
    }

    /**
     * Comentários do lead vinculado — mesma listagem da tela abrir-cliente
     * (autor, data, fixado_proprio; fixados no topo).
     */
    public function leadComentarios(int $conversaId): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa) {
            return response()->json(['success' => false, 'message' => 'Conversa não encontrada.'], 404);
        }

        if (! $conversa->contato_id) {
            return response()->json(['success' => false, 'message' => 'A conversa não está vinculada a um cliente.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->comentariosRepository->getCommentsMailingAll($conversa->contato_id),
        ]);
    }

    /**
     * Altera a temperatura do lead vinculado — mesma regra do funil comercial.
     */
    public function leadTemperatura(int $conversaId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'temperatura' => 'required|in:QUENTE,MORNO,FRIO',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa || ! $conversa->contato_id) {
            return response()->json(['success' => false, 'message' => 'Conversa sem cliente vinculado.'], 422);
        }

        if (! $this->conversaUseCase->podeInteragir(Auth::user(), $conversa)) {
            return response()->json(['success' => false, 'message' => 'Apenas o vendedor da conversa pode alterar a temperatura.'], 403);
        }

        $ok = $this->contatosCorretoresRepository->updateLeadTemperature(
            $conversa->contato_id,
            $request->input('temperatura')
        );

        return response()->json(['success' => (bool) $ok]);
    }

    /**
     * Carteira do cliente vinculado: vendas realizadas (produtos, valores,
     * status administrativo) e dependentes — o histórico completo.
     */
    public function leadCarteira(int $conversaId): JsonResponse
    {
        $conversa = $this->resolverConversa($conversaId);

        if (! $conversa) {
            return response()->json(['success' => false, 'message' => 'Conversa não encontrada.'], 404);
        }

        if (! $conversa->contato_id) {
            return response()->json(['success' => false, 'message' => 'A conversa não está vinculada a um cliente.'], 422);
        }

        $vendas = DB::table('vendas as v')
            ->leftJoin('tabulacoes as t', function ($join) {
                $join->on('t.id', '=', 'v.tabulacao_id')
                    ->on('t.empresa_id', '=', 'v.empresa_id');
            })
            ->where('v.contato_id', $conversa->contato_id)
            ->where('v.empresa_id', $conversa->empresa_id)
            ->orderByDesc('v.created_at')
            ->get([
                'v.id',
                'v.numero_proposta',
                'v.nome_contrato',
                'v.operadora',
                'v.nome_plano',
                'v.valor_contrato',
                'v.vidas',
                'v.data_vigencia',
                'v.data_implantacao',
                'v.created_at',
                't.descricao as status',
            ])
            ->map(function ($venda) {
                $venda->created_at = $venda->created_at
                    ? \Carbon\Carbon::parse($venda->created_at)->timezone('America/Sao_Paulo')->format('d/m/Y')
                    : null;
                $venda->data_vigencia = $venda->data_vigencia
                    ? \Carbon\Carbon::parse($venda->data_vigencia)->format('d/m/Y')
                    : null;
                $venda->data_implantacao = $venda->data_implantacao
                    ? \Carbon\Carbon::parse($venda->data_implantacao)->format('d/m/Y')
                    : null;

                return $venda;
            });

        $dependentes = DB::table('dependentes')
            ->where('contato_id', $conversa->contato_id)
            ->where('empresa_id', $conversa->empresa_id)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpf', 'idade', 'parentesco', 'valor_plano']);

        return response()->json([
            'success' => true,
            'data' => [
                'vendas' => $vendas,
                'dependentes' => $dependentes,
            ],
        ]);
    }

    private function resolverConversa(int $conversaId)
    {
        $user = Auth::user();

        return $this->conversaRepository->findParaUsuario(
            $conversaId,
            $this->tenantId(),
            $this->conversaUseCase->escopoUsuario($user)
        );
    }

    private function serializarConversa($conversa, bool $completa = false): array
    {
        $dados = [
            'id' => $conversa->id,
            'numero' => $conversa->numero,
            'nome_whatsapp' => $conversa->nome_whatsapp,
            'foto_url' => $conversa->foto_url,
            'contato_id' => $conversa->contato_id,
            'contato_nome' => $conversa->contato?->nome_cliente,
            'tabulacao_id' => $conversa->tabulacao_id,
            'tabulacao_descricao' => $conversa->tabulacao?->descricao,
            'last_message_at' => $conversa->last_message_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
            'last_message_preview' => $conversa->last_message_preview,
            'unread_count' => $conversa->unread_count,
            'arquivada' => $conversa->arquivada === 'Y',
            'user_id' => $conversa->user_id,
            'vendedor_nome' => $conversa->vendedor?->name,
        ];

        if ($completa && $conversa->contato) {
            $dados['carteira'] = DB::table('vendas')
                ->where('contato_id', $conversa->contato_id)
                ->where('empresa_id', $conversa->empresa_id)
                ->exists();

            $dados['contato'] = [
                'id' => $conversa->contato->id,
                'temperatura' => DB::table('contatos_corretores')
                    ->where('contato_id', $conversa->contato_id)
                    ->where('user_id', $conversa->user_id)
                    ->where('empresa_id', $conversa->empresa_id)
                    ->value('temperatura'),
                'nome_cliente' => $conversa->contato->nome_cliente,
                'cpf' => $conversa->contato->cpf,
                'plano' => $conversa->contato->plano,
                'categoria' => $conversa->contato->categoria,
                'entidade' => $conversa->contato->entidade,
                'idades' => $conversa->contato->idades,
                'valor_plano_atual' => $conversa->contato->valor_plano_atual,
                'telefone1' => $conversa->contato->telefone1,
                'telefone2' => $conversa->contato->telefone2,
                'telefone3' => $conversa->contato->telefone3,
                'email' => $conversa->contato->email,
            ];
        }

        return $dados;
    }
}
