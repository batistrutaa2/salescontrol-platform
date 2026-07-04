<?php

namespace App\Repositories\Eloquent;

use App\Models\WhatsappMensagem;
use App\Repositories\Contracts\WhatsappMensagemRepositoryInterface;
use Illuminate\Support\Collection;

class WhatsappMensagemRepository implements WhatsappMensagemRepositoryInterface
{
    protected $model;

    public function __construct(WhatsappMensagem $model)
    {
        $this->model = $model;
    }

    /**
     * Thread paginada para scroll infinito: retorna as $limit mensagens
     * mais recentes anteriores a $beforeId, em ordem cronológica.
     */
    public function getThread(int $conversaId, ?int $beforeId = null, int $limit = 30): Collection
    {
        return $this->model
            ->where('conversa_id', $conversaId)
            ->when($beforeId, fn ($q) => $q->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }
}
