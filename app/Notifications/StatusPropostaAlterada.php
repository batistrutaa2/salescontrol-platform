<?php

namespace App\Notifications;

use App\Enums\TabulationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class StatusPropostaAlterada extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $vendaId,
        public readonly string $novoStatus,
        public readonly ?int $alteradoPorId = null,
        public readonly ?string $alteradoPorNome = null,
        public readonly ?string $tabulacaoCode = null,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $isEstorno = $this->tabulacaoCode === TabulationCode::ESTORNO;

        return [
            'tipo' => $isEstorno ? 'venda_estornada' : 'status_venda',
            'titulo' => $isEstorno
                ? "Sua venda #{$this->vendaId} foi estornada"
                : "Status da venda #{$this->vendaId} Atualizado",
            'mensagem' => $isEstorno
                ? 'Sua proposta foi estornada — abra para corrigir e reenviar ao backoffice.'
                : "O status foi alterado para {$this->novoStatus}.",
            'venda_id' => $this->vendaId,
            'status' => $this->novoStatus,
            'url' => $isEstorno
                ? route('sale.editEstorno', ['id' => $this->vendaId])
                : route('sale.listSale'),
            'criado_por' => $this->alteradoPorNome ?? 'Sistema',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
