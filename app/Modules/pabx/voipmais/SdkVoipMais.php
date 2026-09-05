<?php

namespace App\Modules\pabx\voipmais;

use Illuminate\Support\Facades\Http;

class SdkVoipMais
{
    protected string $url;

    protected string $token;

    public function __construct(string $url, string $token)
    {
        $this->url = $url;
        $this->token = $token;
    }

    public function makeClickToCall($ramal, $destino)
    {
        try {
            $response = Http::get($this->url, [
                'api_token' => $this->token,
                'ramal' => $ramal,
                'destino' => $destino,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Erro na chamada: '.$response->status(),
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => 'Não foi possível iniciar a ligação.',
            ];
        }
    }
}
