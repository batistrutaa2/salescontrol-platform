<?php

namespace App\Events\Whatsapp;

use App\Models\WhatsappMensagem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaMensagemWhatsapp implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WhatsappMensagem $mensagem,
        public int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('whatsapp.vendedor.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.mensagem.nova';
    }

    public function broadcastWith(): array
    {
        return [
            'conversa_id' => $this->mensagem->conversa_id,
            'mensagem' => [
                'id' => $this->mensagem->id,
                'message_id' => $this->mensagem->message_id,
                'direcao' => $this->mensagem->direcao,
                'tipo' => $this->mensagem->tipo,
                'body' => $this->mensagem->body,
                'media_url' => $this->mensagem->media_url,
                'media_mime' => $this->mensagem->media_mime,
                'quoted_message_id' => $this->mensagem->quoted_message_id,
                'ack' => $this->mensagem->ack,
                'status_envio' => $this->mensagem->status_envio,
                'message_timestamp' => $this->mensagem->message_timestamp?->toIso8601String(),
            ],
        ];
    }
}
