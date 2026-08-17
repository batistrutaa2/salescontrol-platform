<?php

use App\Services\Documentos\VendaDocumentoPermissionPolicy;

return [

    /*
  |--------------------------------------------------------------------------
  | Default Filesystem Disk
  |--------------------------------------------------------------------------
  |
  | Here you may specify the default filesystem disk that should be used
  | by the framework. The "local" disk, as well as a variety of cloud
  | based disks are available to your application for file storage.
  |
  */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
  |--------------------------------------------------------------------------
  | Filesystem Disks
  |--------------------------------------------------------------------------
  |
  | Below you may configure as many filesystem disks as necessary, and you
  | may even configure multiple disks for the same driver. Examples for
  | most supported storage drivers are configured here for reference.
  |
  | Supported Drivers: "local", "ftp", "sftp", "s3"
  |
  */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'documentos_sftp' => [
            'driver' => 'sftp',
            'host' => env('DOCUMENTOS_SFTP_HOST'),
            'username' => env('DOCUMENTOS_SFTP_USERNAME'),
            'privateKey' => env('DOCUMENTOS_SFTP_PRIVATE_KEY'),
            'passphrase' => env('DOCUMENTOS_SFTP_PASSPHRASE'),
            'hostFingerprint' => env('DOCUMENTOS_SFTP_FINGERPRINT'),
            'port' => (int) env('DOCUMENTOS_SFTP_PORT', 22),
            'root' => env('DOCUMENTOS_SFTP_BASE_PATH', '/'),
            'timeout' => (int) env('DOCUMENTOS_SFTP_TIMEOUT', 45),
            // O retry do job aplica backoff; tentativas internas longas prenderiam o worker.
            'maxTries' => (int) env('DOCUMENTOS_SFTP_CONNECT_TRIES', 1),
            'visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
            'directory_visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
            'permissions' => VendaDocumentoPermissionPolicy::permissionMap(),
            'throw' => true,
        ],

    ],

    /*
  |--------------------------------------------------------------------------
  | Symbolic Links
  |--------------------------------------------------------------------------
  |
  | Here you may configure the symbolic links that will be created when the
  | `storage:link` Artisan command is executed. The array keys should be
  | the locations of the links and the values should be their targets.
  |
  */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
