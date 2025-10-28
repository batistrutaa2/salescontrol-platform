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
