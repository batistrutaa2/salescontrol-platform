<?php

use App\Enums\UserRole;
use App\Models\Vendas;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Broadcast;

$isActiveTenant = static function (int $empresaId): bool {
    $tenantContext = app(TenantContext::class);

    return $tenantContext->isResolved() && $tenantContext->id() === $empresaId;
};

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal para notificações de contratos implantados - apenas usuários administrativos
Broadcast::channel('contratos.administrativo.{empresaId}', function ($user, $empresaId) use ($isActiveTenant) {
    return $isActiveTenant((int) $empresaId)
        && ($user->isPlatformAdmin() || (int) $user->user_role_id === UserRole::ADMINISTRATIVO);
});

// Conversas/mensagens WhatsApp do vendedor — apenas o próprio dono (feature individual)
Broadcast::channel('whatsapp.vendedor.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Status/QR da instância WhatsApp — apenas o próprio vendedor
Broadcast::channel('whatsapp.instancia.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('venda-documentos.{empresaId}.{vendaId}', function ($user, $empresaId, $vendaId) use ($isActiveTenant) {
    if (! $isActiveTenant((int) $empresaId)) {
        return false;
    }

    if ($user->isPlatformAdmin() || in_array((int) $user->user_role_id, [
        UserRole::ADMINISTRATIVO,
        UserRole::BACKOFFICE,
        UserRole::DEVELOPER,
    ], true)) {
        return true;
    }

    return Vendas::query()
        ->whereKey($vendaId)
        ->where('empresa_id', $empresaId)
        ->where('user_id', $user->id)
        ->exists();
});
