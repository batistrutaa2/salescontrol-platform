<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/3gpp' => '3gp',
        'audio/ogg' => 'ogg',
        'audio/ogg; codecs=opus' => 'ogg',
        'audio/mpeg' => 'mp3',
        'audio/mp4' => 'm4a',
        'audio/webm' => 'webm',
        'application/pdf' => 'pdf',
    ];

    /**
     * Salva mídia em base64 no disk public e retorna [path, size].
     */
    public function salvarBase64(string $base64, string $mime, int $empresaId, int $conversaId, ?string $fileName = null): array
    {
        // Remove prefixo data-uri, se presente
        if (str_contains($base64, ',')) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
        }

        $conteudo = base64_decode($base64, true);

        if ($conteudo === false) {
            throw new \InvalidArgumentException('Base64 de mídia inválido.');
        }

        $extensao = $this->extensao($mime, $fileName);
        $path = sprintf('whatsapp/%d/%d/%s.%s', $empresaId, $conversaId, (string) Str::uuid(), $extensao);

        Storage::disk('public')->put($path, $conteudo);

        return [$path, strlen($conteudo)];
    }

    private function extensao(string $mime, ?string $fileName): string
    {
        $mimeBase = strtok($mime, ';');

        if (isset(self::MIME_EXTENSIONS[$mime])) {
            return self::MIME_EXTENSIONS[$mime];
        }

        if (isset(self::MIME_EXTENSIONS[$mimeBase])) {
            return self::MIME_EXTENSIONS[$mimeBase];
        }

        if ($fileName && str_contains($fileName, '.')) {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (preg_match('/^[a-z0-9]{1,8}$/', $ext)) {
                return $ext;
            }
        }

        $sub = strtolower(substr((string) strrchr($mimeBase, '/'), 1));

        return preg_match('/^[a-z0-9]{1,8}$/', $sub) ? $sub : 'bin';
    }
}
