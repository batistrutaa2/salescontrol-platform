<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendamentoNotificacao extends Notification
{
    public $agendamento;

    public function __construct($agendamento)
    {
        $this->agendamento = $agendamento;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'tipo' => 'agendamento',
            'titulo' => 'Você tem um contato agendado agora!',
            'data_inicio' => \Carbon\Carbon::parse($this->agendamento->horario_agendamento)->format('d/m/Y H:i'),
            'contato_id' => $this->agendamento->contato_id,
            'criado_por' => $this->agendamento->user->name ?? 'Desconhecido',
            'url' => route('comercial.openClient', ['id_mailing' => $this->agendamento->contato_id]),
            'agendado_por' => $this->agendamento->user_id
        ];
    }

}