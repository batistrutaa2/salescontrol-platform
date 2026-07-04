<?php

namespace App\Events\Whatsapp;

use App\Models\WhatsappInstancia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StatusInstanciaAtualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WhatsappInstancia $instancia) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('whatsapp.instancia.'.$this->instancia->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.instancia.status';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => $this->instancia->status,
            'numero_conectado' => $this->instancia->numero_conectado,
            'connected_at' => $this->instancia->connected_at?->toIso8601String(),
        ];
    }
}
