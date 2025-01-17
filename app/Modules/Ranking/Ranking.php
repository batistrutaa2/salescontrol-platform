<?php

namespace App\Modules\Ranking;

use Illuminate\Support\Facades\Http;

class Ranking
{
  private $url;
  private $token;

  public function __construct()
  {
    $this->url = config('services.rankingdevendas.url');
    $this->token = config('services.rankingdevendas.token');
  }

  public function getUsers()
  {
    $response = Http::withHeaders([
      'Authorization' => "Bearer {$this->token}",
      'Content-Type' => 'application/json',
    ])->get($this->url . "/users");

    dd($response);
  }

  public function updateSales()
  {

  }

  public function execRequest()
  {

  }
}
