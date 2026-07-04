<?php

namespace App\Events\Whatsapp;

use App\Models\WhatsappConversa;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversaWhatsappAtualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WhatsappConversa $conversa) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('whatsapp.vendedor.'.$this->conversa->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.conversa.atualizada';
    }

    public function broadcastWith(): array
    {
        return [
            'conversa' => [
                'id' => $this->conversa->id,
                'remote_jid' => $this->conversa->remote_jid,
                'numero' => $this->conversa->numero,
                'nome_whatsapp' => $this->conversa->nome_whatsapp,
                'foto_url' => $this->conversa->foto_url,
                'contato_id' => $this->conversa->contato_id,
                'tabulacao_id' => $this->conversa->tabulacao_id,
                'last_message_at' => $this->conversa->last_message_at?->toIso8601String(),
                'last_message_preview' => $this->conversa->last_message_preview,
                'unread_count' => $this->conversa->unread_count,
            ],
        ];
    }
}
