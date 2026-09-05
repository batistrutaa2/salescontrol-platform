<?php

namespace App\Services\Enrichment;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LemitService
{
    private string $apiToken;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiToken = (string) config('services.lemit.api_key', '');
        $this->baseUrl = rtrim((string) config('services.lemit.base_url'), '/');
    }

    public function consultarCpf(string $cpf): array
    {
        $this->ensureConfigured();
        $cpf = preg_replace('/\D+/', '', $cpf);
        if (strlen($cpf) !== 11) {
            throw new \InvalidArgumentException('CPF inválido.');
        }

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
        ])->post($this->baseUrl.'/pessoa', [
            'documento' => $cpf,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Lemit: falha ao consultar CPF (HTTP '.$response->status().').');
        }

        $data = $response->json();

        return array_merge(['fonte' => 'api_lemit'], $data);
    }

    public function consultarCnpj(string $cnpj): array
    {
        $this->ensureConfigured();
        $cnpj = preg_replace('/\D+/', '', $cnpj);
        if (strlen($cnpj) !== 14) {
            throw new \InvalidArgumentException('CNPJ inválido.');
        }

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer '.$this->apiToken,
        ])->post($this->baseUrl.'/empresa', [
            'documento' => $cnpj,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Lemit: falha ao consultar CNPJ (HTTP '.$response->status().').');
        }

        $json = $response->json();

        return array_merge(['fonte' => 'api_lemit'], $json);
    }

    private function ensureConfigured(): void
    {
        if ($this->apiToken === '' || $this->baseUrl === '') {
            throw new RuntimeException('Lemit: credenciais ausentes. Configure LEMIT_API_TOKEN e LEMIT_BASE_URL no .env');
        }
    }
}
