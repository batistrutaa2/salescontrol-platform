<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\BoletoLembrete;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Notifications\Notification;

class BoletoVencimentoNotification extends Notification
{
    public function __construct(private BoletoLembrete $lembrete) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'boleto_vencimento',
            'empresa_id' => (int) $this->lembrete->empresa_id,
            'lembrete_id' => $this->lembrete->id,
            'titulo' => 'Vencimento de boleto',
            'mensagem' => $this->lembrete->venda->nome_contrato.' — boleto com vencimento em '.$this->lembrete->vencimento->format('d/m/Y').'. Confira o envio e o acompanhamento.',
            'url' => route('backoffice.boletos.index', ['lembrete' => $this->lembrete->id]),
        ];
    }

    public static function visibleTo($notification, User $user): bool
    {
        if (($notification->data['tipo'] ?? null) !== 'boleto_vencimento') {
            return true;
        }

        $context = app(TenantContext::class);

        return $context->isResolved()
            && (int) ($notification->data['empresa_id'] ?? 0) === $context->id()
            && in_array((int) $user->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE], true)
            && $user->ativo === 'Y'
            && ! $user->isPlatformAdmin();
    }
}
