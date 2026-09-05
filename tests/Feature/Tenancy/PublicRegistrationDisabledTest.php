<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicRegistrationDisabledTest extends TestCase
{
    public function test_auto_cadastro_publico_nao_e_registrado(): void
    {
        $this->assertFalse(Route::has('register'));

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Usuário sem empresa',
            'email' => 'intruso@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertNotFound();
    }

    public function test_webhook_resend_sem_assinatura_falha_fechado(): void
    {
        $this->postJson('/resend/webhook', [
            'type' => 'email.delivered',
            'data' => ['email_id' => 'forjado'],
        ])->assertForbidden();
    }
}
