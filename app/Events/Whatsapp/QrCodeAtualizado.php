<?php

namespace App\Events\Whatsapp;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Sinal leve de que há um novo QR code disponível — o front busca o
 * base64 via GET /whatsapp/conexao/qr (o payload não cabe no websocket).
 */
class QrCodeAtualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $userId) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('whatsapp.instancia.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.instancia.qrcode';
    }

    public function broadcastWith(): array
    {
        return [
            'atualizado_em' => now()->toIso8601String(),
        ];
    }
}
