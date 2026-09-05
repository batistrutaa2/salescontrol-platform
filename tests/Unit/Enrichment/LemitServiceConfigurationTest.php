<?php

namespace Tests\Unit\Enrichment;

use App\Services\Enrichment\LemitService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class LemitServiceConfigurationTest extends TestCase
{
    public function test_credenciais_sao_validadas_somente_quando_a_fonte_e_consultada(): void
    {
        config([
            'services.lemit.api_key' => '',
            'services.lemit.base_url' => '',
        ]);

        $service = new LemitService;

        $this->expectException(RuntimeException::class);
        $service->consultarCpf('11144477735');
    }

    public function test_cache_indisponivel_nao_impede_consulta_da_api(): void
    {
        config([
            'services.lemit.api_key' => 'token-teste',
            'services.lemit.base_url' => 'https://lemit.test',
        ]);
        Http::fake([
            'https://lemit.test/pessoa' => Http::response([
                'pessoa' => null,
                'data_consulta' => now()->toISOString(),
            ]),
        ]);

        $resultado = (new LemitService)->consultarCpf('11144477735');

        $this->assertSame('api_lemit', $resultado['fonte']);
        Http::assertSent(fn ($request) => $request->url() === 'https://lemit.test/pessoa'
            && $request['documento'] === '11144477735');
    }
}
