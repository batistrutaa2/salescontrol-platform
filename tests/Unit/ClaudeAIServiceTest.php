<?php

namespace Tests\Unit;

use App\Services\ClaudeAIService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeAIServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.anthropic.api_key' => 'test-api-key',
            'services.anthropic.base_url' => 'https://anthropic.example.test/v9',
            'services.anthropic.model' => 'tenant-safe-model',
            'services.anthropic.version' => '2099-01-01',
            'services.anthropic.max_tokens' => 777,
        ]);
    }

    public function test_usa_somente_a_configuracao_da_plataforma(): void
    {
        Http::fake([
            'https://anthropic.example.test/v9/messages' => Http::response([
                'content' => [['text' => 'Análise segura']],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
            ]),
        ]);

        $resultado = (new ClaudeAIService())->analisarClienteESugerirEstrategias(
            ['nome_cliente' => 'Cliente'],
            []
        );

        $this->assertTrue($resultado['success']);
        $this->assertSame('Análise segura', $resultado['sugestoes']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://anthropic.example.test/v9/messages'
            && $request->hasHeader('x-api-key', 'test-api-key')
            && $request->hasHeader('anthropic-version', '2099-01-01')
            && $request['model'] === 'tenant-safe-model'
            && $request['max_tokens'] === 777);
    }

    public function test_nao_expoe_detalhe_de_erro_do_provedor(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => ['message' => 'detalhe interno e credencial do provedor'],
            ], 500),
        ]);

        $resultado = (new ClaudeAIService())->analisarClienteESugerirEstrategias(
            ['nome_cliente' => 'Cliente'],
            []
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Não foi possível gerar a análise neste momento.', $resultado['error']);
        $this->assertStringNotContainsString('credencial', $resultado['error']);
    }
}
