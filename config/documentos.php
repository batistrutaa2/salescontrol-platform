<?php

return [
    'processamento_ativo' => env('DOCUMENTOS_PROCESSAMENTO_ATIVO', false),
    'disk' => env('DOCUMENTOS_DISK', 'documentos_sftp'),
    'root' => trim(env('DOCUMENTOS_ROOT', 'EmAnalise'), '/'),
    'max_files' => 30,
    'max_kilobytes' => 25 * 1024,
    'clamav' => [
        'host' => env('CLAMAV_HOST', 'clamav'),
        'port' => env('CLAMAV_PORT', 3310),
        'timeout' => env('CLAMAV_TIMEOUT', 120),
    ],
];
