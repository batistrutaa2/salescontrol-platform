<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class StatusPropostaAlterada extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly object $venda,
        public readonly string $novoStatus,
        public readonly ?int $alteradoPorId = null,
        public readonly ?string $alteradoPorNome = null,
    ) {
    }

    public function via($notifiable)
    {
        // database p/ aparecer no seu dropdown; broadcast se quiser tempo real depois
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'tipo' => 'status_venda',
            'titulo' => "Status da venda #{$this->venda->id} Atualizado",
            'mensagem' => "O status foi alterado para {$this->novoStatus}.",
            'venda_id' => $this->venda->id,
            'status' => $this->novoStatus,
            'url' => route('sale.listSale'),
            'criado_por' => $this->alteradoPorNome ?? 'Sistema',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
