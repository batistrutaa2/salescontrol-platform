<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa o vendedor de que o backoffice puxou de volta uma venda que estava
 * em estorno — ela sai de "Meus Estornos" e o reenvio deixa de ser necessário.
 */
class VendaRetomadaPeloBackoffice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $vendaId,
        public readonly string $nomeContrato,
        public readonly string $novoStatus,
        public readonly ?string $backofficeNome,
        public readonly ?string $observacao,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $mensagem = "O backoffice retomou o contrato {$this->nomeContrato} para a fila ({$this->novoStatus}). Não é preciso reenviar.";

        if ($this->observacao) {
            $mensagem .= " Motivo: {$this->observacao}";
        }

        return [
            'tipo' => 'venda_retomada_backoffice',
            'titulo' => "Venda #{$this->vendaId} retomada pelo backoffice",
            'mensagem' => $mensagem,
            'venda_id' => $this->vendaId,
            'status' => $this->novoStatus,
            'observacao' => $this->observacao,
            'url' => route('sale.listSale'),
            'criado_por' => $this->backofficeNome ?? 'Backoffice',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
