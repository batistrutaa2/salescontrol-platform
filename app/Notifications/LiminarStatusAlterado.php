<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class LiminarStatusAlterado extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $liminarId,
        public readonly string $nomeContrato,
        public readonly string $nomeBeneficiario,
        public readonly string $campoAlterado,
        public readonly ?string $valorAnterior,
        public readonly string $valorNovo,
        public readonly ?string $alteradoPorNome = null,
        public readonly bool $enviarEmail = false,
    ) {
    }

    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];
        if ($this->enviarEmail) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $campo = match ($this->campoAlterado) {
            'status' => 'Status',
            'fase'   => 'Fase',
            default  => $this->campoAlterado,
        };

        return [
            'tipo'             => 'liminar_status',
            'titulo'           => "Liminar de {$this->nomeContrato} atualizada",
            'mensagem'         => "{$campo} alterado para: {$this->valorNovo}.",
            'liminar_id'       => $this->liminarId,
            'nome_contrato'    => $this->nomeContrato,
            'beneficiario'     => $this->nomeBeneficiario,
            'campo_alterado'   => $this->campoAlterado,
            'valor_novo'       => $this->valorNovo,
            'url'              => route('backoffice.liminar.index'),
            'criado_por'       => $this->alteradoPorNome ?? 'Sistema',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Liminar Concedida — {$this->nomeContrato}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("A liminar do contrato **{$this->nomeContrato}** foi concedida.")
            ->line("Beneficiário: {$this->nomeBeneficiario}")
            ->action('Ver no Sistema', route('backoffice.liminar.index'))
            ->line('Entre no sistema para acompanhar os próximos passos.');
    }
}
