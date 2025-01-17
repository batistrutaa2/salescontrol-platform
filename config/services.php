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
    'token' => "XJ13LDJ1CAZBKD7BE3K73UFMSER945",
    'url' => "https://integration.rankingdevendas.com.br/v2",
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

];
