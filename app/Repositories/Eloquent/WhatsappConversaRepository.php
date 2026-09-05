<?php

namespace App\Repositories\Eloquent;

use App\Models\WhatsappConversa;
use App\Repositories\Contracts\WhatsappConversaRepositoryInterface;
use Illuminate\Support\Collection;

class WhatsappConversaRepository implements WhatsappConversaRepositoryInterface
{
    protected $model;

    public function __construct(WhatsappConversa $model)
    {
        $this->model = $model;
    }

    /**
     * Lista de conversas para o painel de chat.
     * $userId null = visão de toda a empresa (supervisão).
     */
    /**
     * Subquery: existe venda do contato vinculado (define a "carteira").
     */
    private function vendaDoContato(): \Closure
    {
        return function ($query) {
            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('vendas')
                ->whereColumn('vendas.contato_id', 'whatsapp_conversas.contato_id')
                ->whereColumn('vendas.empresa_id', 'whatsapp_conversas.empresa_id');
        };
    }

    /**
     * $modo: 'ativas' (funil, sem venda) | 'carteira' (clientes com venda) | 'arquivadas'
     */
    public function getConversasLista(int $empresaId, ?int $userId, ?string $busca = null, string $modo = 'ativas'): Collection
    {
        return $this->model
            ->with([
                'contato' => fn ($query) => $query->select('id', 'nome_cliente', 'plano', 'categoria')->where('contatos.empresa_id', $empresaId),
                'vendedor' => fn ($query) => $query->select('id', 'name')->tenantMember($empresaId),
                'tabulacao' => fn ($query) => $query->select('id', 'descricao')->where('tabulacoes.empresa_id', $empresaId),
            ])
            ->where('empresa_id', $empresaId)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->when($busca, function ($q) use ($busca) {
                $digitos = preg_replace('/\D/', '', $busca);
                $q->where(function ($sub) use ($busca, $digitos) {
                    $sub->where('nome_whatsapp', 'LIKE', "%{$busca}%")
                        ->orWhereHas('contato', fn ($c) => $c->where('nome_cliente', 'LIKE', "%{$busca}%"));
                    if ($digitos !== '') {
                        $sub->orWhere('numero', 'LIKE', "%{$digitos}%");
                    }
                });
            })
            ->when($modo === 'arquivadas', fn ($q) => $q->where('arquivada', 'Y'))
            ->when($modo !== 'arquivadas', fn ($q) => $q->where('arquivada', 'N'))
            ->when($modo === 'carteira', fn ($q) => $q->whereNotNull('contato_id')->whereExists($this->vendaDoContato()))
            ->when($modo === 'ativas', fn ($q) => $q->whereNotExists($this->vendaDoContato()))
            ->orderByDesc('last_message_at')
            ->limit(300)
            ->get();
    }

    /**
     * Conversas agrupáveis no kanban (com tabulação).
     */
    public function getConversasKanban(int $empresaId, ?int $userId): Collection
    {
        return $this->model
            ->with([
                'contato' => fn ($query) => $query->select('id', 'nome_cliente', 'telefone1')->where('contatos.empresa_id', $empresaId),
                'vendedor' => fn ($query) => $query->select('id', 'name')->tenantMember($empresaId),
            ])
            ->leftJoin('contatos_corretores as cc', function ($join) {
                $join->on('cc.contato_id', '=', 'whatsapp_conversas.contato_id')
                    ->on('cc.user_id', '=', 'whatsapp_conversas.user_id')
                    ->on('cc.empresa_id', '=', 'whatsapp_conversas.empresa_id');
            })
            ->select('whatsapp_conversas.*', 'cc.temperatura as lead_temperatura')
            ->where('whatsapp_conversas.empresa_id', $empresaId)
            ->when($userId !== null, fn ($q) => $q->where('whatsapp_conversas.user_id', $userId))
            ->where('arquivada', 'N')
            // Cliente com venda sai do funil — pertence à carteira
            ->whereNotExists($this->vendaDoContato())
            ->orderByDesc('last_message_at')
            ->get();
    }

    public function findParaUsuario(int $conversaId, int $empresaId, ?int $userId): ?WhatsappConversa
    {
        return $this->model
            ->with([
                'contato' => fn ($query) => $query->where('contatos.empresa_id', $empresaId),
                'vendedor' => fn ($query) => $query->select('id', 'name')->tenantMember($empresaId),
                'instancia' => fn ($query) => $query->where('whatsapp_instancias.empresa_id', $empresaId),
            ])
            ->where('id', $conversaId)
            ->where('empresa_id', $empresaId)
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->first();
    }

    public function changeStatusConversa(int $conversaId, int $empresaId, int $tabulacaoId): bool
    {
        $conversa = $this->model->where('id', $conversaId)->where('empresa_id', $empresaId)->first();
        if (! $conversa) {
            return false;
        }

        $conversa->tabulacao_id = $tabulacaoId;

        return $conversa->save();
    }

    public function vincularContato(int $conversaId, int $empresaId, ?int $contatoId): bool
    {
        $conversa = $this->model->where('id', $conversaId)->where('empresa_id', $empresaId)->first();
        if (! $conversa) {
            return false;
        }

        $conversa->contato_id = $contatoId;

        return $conversa->save();
    }

    public function zerarNaoLidas(int $conversaId, int $empresaId): void
    {
        $this->model->where('id', $conversaId)->where('empresa_id', $empresaId)->update(['unread_count' => 0]);
    }

    public function setArquivada(int $conversaId, int $empresaId, bool $arquivada): bool
    {
        return (bool) $this->model->where('id', $conversaId)->where('empresa_id', $empresaId)
            ->update(['arquivada' => $arquivada ? 'Y' : 'N']);
    }
}
