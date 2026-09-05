<?php

namespace Tests\Unit\Enrichment;

use App\Services\Enrichment\Assertiva\AssertivaTokenManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AssertivaTokenManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.assertiva.client_id' => 'cli',
            'services.assertiva.client_secret' => 'sec',
            'services.assertiva.base_url' => 'https://api.assertivasolucoes.com.br',
        ]);
        Cache::flush();
    }

    public function test_obtem_e_cacheia_o_token(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok-123', 'expires_in' => 3600]),
        ]);

        $manager = new AssertivaTokenManager;

        $this->assertSame('tok-123', $manager->getToken());
        // segunda chamada vem do cache: sem novo request
        $this->assertSame('tok-123', $manager->getToken());
        Http::assertSentCount(1);
    }

    public function test_envia_basic_auth_com_credenciais_codificadas(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 100]),
        ]);

        (new AssertivaTokenManager)->getToken();

        Http::assertSent(function ($request) {
            return $request['grant_type'] === 'client_credentials'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('cli:sec'));
        });
    }

    public function test_renovar_forca_nova_consulta(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::sequence()
                ->push(['access_token' => 'tok-1', 'expires_in' => 3600])
                ->push(['access_token' => 'tok-2', 'expires_in' => 3600]),
        ]);

        $manager = new AssertivaTokenManager;
        $this->assertSame('tok-1', $manager->getToken());
        $this->assertSame('tok-2', $manager->renovar());
        Http::assertSentCount(2);
    }

    public function test_lanca_excecao_sem_credenciais(): void
    {
        config(['services.assertiva.client_id' => '', 'services.assertiva.client_secret' => '']);

        $this->expectException(RuntimeException::class);
        new AssertivaTokenManager;
    }

    public function test_lanca_excecao_quando_api_falha(): void
    {
        Http::fake(['*/oauth2/v3/token' => Http::response([], 401)]);

        $this->expectException(RuntimeException::class);
        (new AssertivaTokenManager)->getToken();
    }
}
