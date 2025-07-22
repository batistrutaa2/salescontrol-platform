<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class NovaReuniaoAgendada extends Notification
{
    use Queueable;

    protected $reuniao;


    public function __construct($reuniao)
    {
        $this->reuniao = $reuniao;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //         ->subject('Nova Reunião Agendada')
    //         ->line('Uma nova reunião foi agendada:')
    //         ->line('Título: ' . $this->reuniao->titulo)
    //         ->line('Data: ' . $this->reuniao->data_inicio)
    //         ->action('Ver no sistema', url('/reunioes'));
    // }
    public function toArray($notifiable)
    {
        return [
            'titulo' => $this->reuniao->titulo,
            'data_inicio' => Carbon::parse($this->reuniao->data_inicio)->format('d/m/Y H:i'),
            'criado_por' => auth()->user()->name,
        ];
    }
}
