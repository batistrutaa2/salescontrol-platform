<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StatusPropostaAlterada extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $vendaId,
        public readonly string $novoStatus,
        public readonly ?int $alteradoPorId = null,
        public readonly ?string $alteradoPorNome = null,
    ) {
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'status_venda',
            'titulo' => "Status da venda #{$this->vendaId} Atualizado",
            'mensagem' => "O status foi alterado para {$this->novoStatus}.",
            'venda_id' => $this->vendaId,
            'status' => $this->novoStatus,
            'url' => route('sale.listSale'),
            'criado_por' => $this->alteradoPorNome ?? 'Sistema',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
