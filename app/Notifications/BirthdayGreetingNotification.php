<?php

// app/Notifications/BirthdayGreetingNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BirthdayGreetingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $message) {}

    public function via($notifiable): array
    {
        return ['database']; 
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Feliz Aniversário! 🎂',
            'message' => $this->message,
        ];
    }
}
