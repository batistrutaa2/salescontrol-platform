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

  function updateSaleUserRanking($_id, $value)
  {

    $body = [
      'fieldid' => "678116d3fc9012eaed4ac081",
      'value' => 20000.00,
      'set_points' => true,
    ];

    dd($_id);
    $response = Http::withHeaders([
      'Authorization' => "1578RYEHXOUJDXBWMF4W5G7OACJQTU",
      'Accept' => 'application/json',
    ])->put($this->url . "team/user/edit/addfield/" . $_id, $body);

    if ($response->successful()) {
      return $response->json();
    } else {
      return [
        'error' => true,
        'message' => $response->body(),
      ];
    }
  }

}
