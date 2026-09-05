<?php

namespace App\Services\Enrichment\Assertiva;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Gerencia o access_token OAuth2 (client_credentials) da Assertiva.
 *
 * Diferente do Lemit (token estático), a Assertiva exige obter um token via
 * POST /oauth2/v3/token (Authorization: Basic base64(clientId:secret)), que
 * expira. Aqui o token é cacheado até pouco antes de expirar e renovado sob demanda.
 *
 * Obs.: caso as credenciais já cheguem pré-codificadas, ajustar a montagem do
 * header Basic em {@see basicAuthHeader()} (validar no onboarding).
 */
class AssertivaTokenManager
{
    private const CACHE_KEY = 'assertiva_access_token';

    private const SAFETY_MARGIN_SECONDS = 60;

    private string $clientId;

    private string $clientSecret;

    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = (string) config('services.assertiva.client_id', '');
        $this->clientSecret = (string) config('services.assertiva.client_secret', '');
        $this->baseUrl = rtrim((string) config('services.assertiva.base_url'), '/');

        if ($this->clientId === '' || $this->clientSecret === '' || $this->baseUrl === '') {
            throw new RuntimeException('Assertiva: credenciais ausentes. Configure CLIENTE_ID_ASSERTIVA, TOKEN_SECRET_ASSERTIVA e ASSERTIVA_BASE_URL no .env');
        }
    }

    /**
     * Devolve um access_token válido (do cache ou renovado).
     */
    public function getToken(): string
    {
        $token = Cache::get(self::CACHE_KEY);
        if (is_string($token) && $token !== '') {
            return $token;
        }

        return $this->renovar();
    }

    /**
     * Força a renovação do token (usado após um 401).
     */
    public function renovar(): string
    {
        Cache::forget(self::CACHE_KEY);

        $response = Http::asForm()
            ->withHeaders(['Authorization' => 'Basic '.$this->basicAuthHeader()])
            ->post($this->baseUrl.'/oauth2/v3/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Assertiva: falha ao autenticar (HTTP '.$response->status().').');
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Assertiva: token de acesso não retornado pela API.');
        }

        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        $ttl = max(self::SAFETY_MARGIN_SECONDS, $expiresIn - self::SAFETY_MARGIN_SECONDS);
        Cache::put(self::CACHE_KEY, $token, now()->addSeconds($ttl));

        return $token;
    }

    private function basicAuthHeader(): string
    {
        return base64_encode($this->clientId.':'.$this->clientSecret);
    }
}
