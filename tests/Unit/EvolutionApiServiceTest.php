<?php

namespace Tests\Unit;

use App\Exceptions\EvolutionApiException;
use App\Services\Evolution\EvolutionApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvolutionApiServiceTest extends TestCase
{
    public function test_falha_fechado_quando_gateway_nao_esta_configurado(): void
    {
        config([
            'services.evolution.url' => '',
            'services.evolution.api_key' => '',
        ]);
        Http::fake();

        $this->expectException(EvolutionApiException::class);
        $this->expectExceptionMessage('Serviço de WhatsApp não configurado.');

        try {
            (new EvolutionApiService())->connectionState('instancia-tenant');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_nao_expoe_corpo_de_erro_do_gateway(): void
    {
        config([
            'services.evolution.url' => 'https://evolution.example.test',
            'services.evolution.api_key' => 'gateway-test-key',
        ]);
        Http::fake([
            '*' => Http::response(['message' => 'segredo interno do gateway'], 500),
        ]);

        try {
            (new EvolutionApiService())->connectionState('instancia-tenant');
            $this->fail('A falha do gateway deveria gerar uma exceção sanitizada.');
        } catch (EvolutionApiException $exception) {
            $this->assertSame(500, $exception->status);
            $this->assertStringNotContainsString('segredo interno', $exception->getMessage());
        }
    }
}
