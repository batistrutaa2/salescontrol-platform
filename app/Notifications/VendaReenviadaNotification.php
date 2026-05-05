<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class VendaReenviadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $vendaId,
        public readonly string $nomeContrato,
        public readonly ?string $vendedorNome,
        public readonly ?string $observacaoReenvio,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'venda_reenviada',
            'titulo' => "Venda #{$this->vendaId} reenviada após estorno",
            'mensagem' => "{$this->vendedorNome} corrigiu e reenviou o contrato {$this->nomeContrato}.",
            'venda_id' => $this->vendaId,
            'observacao_reenvio' => $this->observacaoReenvio,
            'url' => route('backoffice.openContract', ['idContrato' => $this->vendaId]),
            'criado_por' => $this->vendedorNome ?? 'Vendedor',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
