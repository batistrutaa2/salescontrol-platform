<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso para o vendedor: o administrativo deu baixa na demanda de pós-venda que
 * ele havia solicitado. Surge no mascote do vendedor.
 */
class DemandaVendedorConcluida extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $demandaId,
        public readonly string $cliente,
        public readonly string $tipoLabel,
        public readonly string $url,
        public readonly ?string $concluidaPorNome = null,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'demanda_vendedor_concluida',
            'titulo' => 'Sua demanda foi concluída ✅',
            'mensagem' => "\"{$this->tipoLabel}\" do cliente {$this->cliente} foi finalizada pelo pós-venda.",
            'demanda_id' => $this->demandaId,
            'status' => 'CONCLUIDA',
            'url' => $this->url,
            'criado_por' => $this->concluidaPorNome ?? 'Pós-venda',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
