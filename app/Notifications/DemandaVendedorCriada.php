<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso para o administrativo/back-office: um vendedor abriu uma demanda de
 * pós-venda. Substitui o pedido informal por WhatsApp. Surge no mascote do admin.
 */
class DemandaVendedorCriada extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $demandaId,
        public readonly string $vendedorNome,
        public readonly string $cliente,
        public readonly string $tipoLabel,
        public readonly string $url,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'demanda_vendedor_nova',
            'titulo' => 'Nova demanda de pós-venda',
            'mensagem' => "{$this->vendedorNome} solicitou \"{$this->tipoLabel}\" para o cliente {$this->cliente}.",
            'demanda_id' => $this->demandaId,
            'status' => null,
            'url' => $this->url,
            'criado_por' => $this->vendedorNome,
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
