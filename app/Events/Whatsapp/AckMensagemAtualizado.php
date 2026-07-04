<?php

namespace App\Events\Whatsapp;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AckMensagemAtualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $conversaId,
        public string $messageId,
        public int $ack
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('whatsapp.vendedor.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.mensagem.ack';
    }

    public function broadcastWith(): array
    {
        return [
            'conversa_id' => $this->conversaId,
            'message_id' => $this->messageId,
            'ack' => $this->ack,
        ];
    }
}
