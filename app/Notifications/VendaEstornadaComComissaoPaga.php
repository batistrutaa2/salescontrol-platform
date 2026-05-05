<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class VendaEstornadaComComissaoPaga extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $vendaId,
        public readonly string $nomeContrato,
        public readonly ?string $vendedorNome,
        public readonly ?string $estornadoPorNome,
        public readonly ?string $motivo,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'estorno_comissao_paga',
            'titulo' => "Estorno em venda com comissão paga (#{$this->vendaId})",
            'mensagem' => "Contrato {$this->nomeContrato} (vendedor {$this->vendedorNome}) foi estornado por {$this->estornadoPorNome}. Comissão já paga — avalie reversão.",
            'venda_id' => $this->vendaId,
            'motivo' => $this->motivo,
            'url' => route('backoffice.index'),
            'criado_por' => $this->estornadoPorNome ?? 'Sistema',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
