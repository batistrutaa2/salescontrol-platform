<?php

return [
  'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
  ],

  'voip' => [
    'maisvoip' => [
      'url' => 'http://painelpabx.maisvoip.com.br:5000/api/v1/clicktocall',
      'token' => '$2y$10$BfEpiUWL5iCghZtuMKOT2ur1hXA1yfhtAhWobY9jzahuNz.xd2WG2'
    ],
  ],

  'rankingdevendas' => [
    'token' => "1578RYEHXOUJDXBWMF4W5G7OACJQTU",
    'url' => "https://integration.rankingdevendas.com.br/v2/",
  ],

  'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
  ],

  'slack' => [
    'notifications' => [
      'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
      'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
    ],
  ],

  'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
  ],

  'lemit' => [
    'api_key' => env('LEMIT_API_TOKEN'),
    'base_url' => env('LEMIT_BASE_URL', 'https://api.lemit.com.br/api/v1/consulta'),
    'cache_months' => (int) env('LEMIT_CACHE_MONTHS', 3),
  ],

];
