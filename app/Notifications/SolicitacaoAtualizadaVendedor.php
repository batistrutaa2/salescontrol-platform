<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso para o vendedor dono do contrato: o pós-venda registrou uma atualização
 * de andamento na solicitação (ex.: "acessei hoje e a portabilidade ainda não
 * saiu"). Surge no mascote do vendedor, mesmo que não tenha sido ele que abriu.
 */
class SolicitacaoAtualizadaVendedor extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $solicitacaoId,
        public readonly string $cliente,
        public readonly string $tipoLabel,
        public readonly string $texto,
        public readonly string $url,
        public readonly ?string $autorNome = null,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'solicitacao_atualizada',
            'titulo' => 'Atualização do pós-venda 📌',
            'mensagem' => "{$this->tipoLabel} do cliente {$this->cliente}: \"{$this->texto}\"",
            'demanda_id' => $this->solicitacaoId,
            'status' => 'ATUALIZACAO',
            'url' => $this->url,
            'criado_por' => $this->autorNome ?? 'Pós-venda',
            'agendado_por' => null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
