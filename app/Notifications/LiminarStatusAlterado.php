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
    ) {
    }

    public function via($notifiable): array
    {
        // Sino (database + broadcast) e e-mail a cada movimentação.
        return ['database', 'broadcast', 'mail'];
    }

    private function campoLabel(): string
    {
        return match ($this->campoAlterado) {
            'status' => 'Status',
            'fase'   => 'Fase',
            default  => ucfirst(str_replace('_', ' ', $this->campoAlterado)),
        };
    }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo'             => 'liminar_status',
            'titulo'           => "Liminar de {$this->nomeContrato} atualizada",
            'mensagem'         => "{$this->campoLabel()} alterado para: {$this->valorNovo}.",
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
            ->subject("Liminar — {$this->nomeContrato}: {$this->valorNovo}")
            ->view('emails.liminar-status-alterado', [
                'usuario'          => $notifiable->name,
                'nomeContrato'     => $this->nomeContrato,
                'nomeBeneficiario' => $this->nomeBeneficiario,
                'campoLabel'       => $this->campoLabel(),
                'valorAnterior'    => $this->valorAnterior,
                'valorNovo'        => $this->valorNovo,
                'alteradoPorNome'  => $this->alteradoPorNome,
                'linkSistema'      => route('backoffice.liminar.index'),
            ]);
    }
}
