<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CotacaoRenovacaoSolicitada extends Notification
{
    use Queueable;
    public function __construct(public int $oportunidadeId, public string $cliente, public string $url) {}
    public function via($notifiable): array { return ['database', 'broadcast']; }
    public function toDatabase($notifiable): array
    {
        return ['tipo' => 'cotacao_renovacao', 'titulo' => 'Cliente quer uma nova cotação',
            'mensagem' => "{$this->cliente} respondeu ao relacionamento e solicitou uma nova cotação.",
            'oportunidade_id' => $this->oportunidadeId, 'url' => $this->url, 'status' => 'COTACAO_SOLICITADA'];
    }
    public function toBroadcast($notifiable): BroadcastMessage { return new BroadcastMessage($this->toDatabase($notifiable)); }
}
