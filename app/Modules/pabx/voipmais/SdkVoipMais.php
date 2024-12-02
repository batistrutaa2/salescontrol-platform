<?php

namespace App\Modules\pabx\Voipmais;

use Illuminate\Support\Facades\Http;

class SdkVoipMais
{
  protected string $url;
  protected string $token;
  public function __construct()
  {
    $this->url = config('services.voip.maisvoip.url');
    $this->token = config('services.voip.maisvoip.token');
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
        'message' => 'Erro na chamada: ' . $response->status(),
      ];
    } catch (\Throwable $th) {
      return [
        'success' => false,
        'message' => 'Erro: ' . $th->getMessage(),
      ];
    }
  }
}
