<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal para notificações de contratos implantados - apenas usuários administrativos
Broadcast::channel('contratos.administrativo.{empresaId}', function ($user, $empresaId) {
    // Apenas usuários com user_role_id = 2 (administrativos) da mesma empresa
    return (int) $user->empresa_id === (int) $empresaId && (int) $user->user_role_id === 2;
});

// Conversas/mensagens WhatsApp do vendedor — o dono ou roles de supervisão (leitura)
Broadcast::channel('whatsapp.vendedor.{userId}', function ($user, $userId) {
    if ((int) $user->id === (int) $userId) {
        return true;
    }

    $dono = \App\Models\User::find($userId);

    return $dono
        && (int) $dono->empresa_id === (int) $user->empresa_id
        && in_array((int) $user->user_role_id, [
            \App\Enums\UserRole::ADMINISTRATIVO,
            \App\Enums\UserRole::DEVELOPER,
            \App\Enums\UserRole::SUPERVISOR,
        ], true);
});

// Status/QR da instância WhatsApp — apenas o próprio vendedor
Broadcast::channel('whatsapp.instancia.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
