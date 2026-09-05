<?php

$fallbackSecret = 'whsec_'.base64_encode(
    hash('sha256', (string) env('APP_KEY'), true)
);

return [
    /*
    | O pacote só instala a verificação de assinatura quando este valor não é
    | vazio. Sem configuração explícita, usamos um segredo privado derivado da
    | chave da aplicação para que o endpoint falhe fechado. Para receber eventos
    | reais, configure o signing secret fornecido pelo Resend.
    */
    'webhook' => [
        'secret' => env('RESEND_WEBHOOK_SECRET') ?: $fallbackSecret,
        'tolerance' => (int) env('RESEND_WEBHOOK_TOLERANCE', 300),
    ],
];
