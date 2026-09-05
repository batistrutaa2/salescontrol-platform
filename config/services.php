<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'whatsapp' => [
        'endpoint' => env('WHATSAPP_API_ENDPOINT'),
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
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-haiku-20240307'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 2048),
    ],

    'lemit' => [
        'api_key' => env('LEMIT_API_TOKEN'),
        'base_url' => env('LEMIT_BASE_URL', 'https://api.lemit.com.br/api/v1/consulta'),
    ],

    'assertiva' => [
        'client_id' => env('CLIENTE_ID_ASSERTIVA'),
        'client_secret' => env('TOKEN_SECRET_ASSERTIVA'),
        'base_url' => env('ASSERTIVA_BASE_URL', 'https://api.assertivasolucoes.com.br'),
        'id_finalidade' => (int) env('ASSERTIVA_ID_FINALIDADE', 5),
        'cache_months' => (int) env('ASSERTIVA_CACHE_MONTHS', 3),
    ],

    'evolution' => [
        'url' => env('EVOLUTION_API_URL', 'http://evolution:8080'),
        'api_key' => env('EVOLUTION_API_KEY'),
        // URL pública que a Evolution usa para entregar webhooks ao Laravel.
        // Em dev (rede Docker) é o container da aplicação; em produção, o domínio HTTPS do CRM.
        'webhook_base_url' => env('EVOLUTION_WEBHOOK_BASE_URL', env('APP_URL')),
    ],

];
