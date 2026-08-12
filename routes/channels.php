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

// Conversas/mensagens WhatsApp do vendedor — apenas o próprio dono (feature individual)
Broadcast::channel('whatsapp.vendedor.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Status/QR da instância WhatsApp — apenas o próprio vendedor
Broadcast::channel('whatsapp.instancia.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('venda-documentos.{empresaId}.{vendaId}', function ($user, $empresaId, $vendaId) {
    if ((int) $user->empresa_id !== (int) $empresaId) return false;
    if (in_array((int) $user->user_role_id, [2, 3, 4], true)) return true;

    return \App\Models\Vendas::whereKey($vendaId)->where('empresa_id', $empresaId)->where('user_id', $user->id)->exists();
});
