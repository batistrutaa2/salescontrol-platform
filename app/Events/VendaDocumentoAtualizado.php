<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendaDocumentoAtualizado implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $vendaId, public int $empresaId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("venda-documentos.{$this->empresaId}.{$this->vendaId}")];
    }

    public function broadcastAs(): string
    {
        return 'documento.atualizado';
    }

    public function broadcastWith(): array
    {
        return ['venda_id' => $this->vendaId];
    }

    public function broadcastQueue(): string
    {
        return 'default';
    }
}
