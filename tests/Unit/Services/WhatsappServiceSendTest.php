<?php

namespace Tests\Unit\Services;

use App\Services\WhatsappService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappServiceSendTest extends TestCase
{
    private WhatsappService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.endpoint' => 'https://whatsapp.example.test/backend/api/messages/send']);
        $this->service = new WhatsappService();
    }

    public function test_por_padrao_nao_abre_ticket(): void
    {
        Http::fake([
            '*example.test*' => Http::response(['ok' => true], 200),
        ]);

        $this->service->send('tok', '(85) 99999-8888', 'Notificação interna');

        Http::assertSent(function (Request $request) {
            $payload = json_decode($request->body(), true);

            return $payload['saveOnTicket'] === false;
        });
    }

    public function test_abre_ticket_quando_save_on_ticket_true(): void
    {
        Http::fake([
            '*example.test*' => Http::response(['ok' => true], 200),
        ]);

        $this->service->send('tok', '(85) 99999-8888', 'Boas-vindas', saveOnTicket: true);

        Http::assertSent(function (Request $request) {
            $payload = json_decode($request->body(), true);

            return $payload['saveOnTicket'] === true
                && $payload['number'] === '5585999998888'
                && $payload['body'] === 'Boas-vindas'
                && $request->hasHeader('Authorization', 'Bearer tok');
        });
    }

    public function test_falha_fechado_sem_endpoint_configurado(): void
    {
        config(['services.whatsapp.endpoint' => null]);
        Http::fake();

        $response = $this->service->send('tok', '5585999998888', 'Mensagem');

        $this->assertFalse($response['success']);
        $this->assertStringContainsString('não configurada', $response['message']);
        Http::assertNothingSent();
    }
}
