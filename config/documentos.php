<?php

return [
    'processamento_ativo' => env('DOCUMENTOS_PROCESSAMENTO_ATIVO', false),
    'disk' => env('DOCUMENTOS_DISK', 'documentos_sftp'),
    'root' => trim(env('DOCUMENTOS_ROOT', 'EmAnalise'), '/'),
    'max_files' => 30,
    'max_kilobytes' => 25 * 1024,
];
