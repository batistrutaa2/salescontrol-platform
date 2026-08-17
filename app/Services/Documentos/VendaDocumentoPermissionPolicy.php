<?php

namespace App\Services\Documentos;

use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;

final class VendaDocumentoPermissionPolicy
{
    public const VISIBILITY = 'private';

    public const FILE_MODE = 0660;

    public const DIRECTORY_MODE = 0770;

    public static function permissionMap(): array
    {
        return [
            'file' => ['public' => self::FILE_MODE, 'private' => self::FILE_MODE],
            'dir' => ['public' => self::DIRECTORY_MODE, 'private' => self::DIRECTORY_MODE],
        ];
    }

    public function applyToFile(Filesystem $disk, string $path): void
    {
        $this->assertProposalDocumentPath($path);
        if (! $disk->setVisibility($path, self::VISIBILITY)) {
            throw new RuntimeException('Não foi possível aplicar a permissão colaborativa ao documento.');
        }
    }

    public function assertProposalDocumentPath(string $path): void
    {
        $root = trim((string) config('documentos.root'), '/');
        $path = trim($path, '/');

        if ($root === '' || $path === '' || str_contains($root, '..') || str_contains($path, '..')) {
            throw new InvalidArgumentException('O caminho documental não é seguro para ajuste de permissão.');
        }

        if (! str_starts_with($path, $root.'/')) {
            throw new InvalidArgumentException('O ajuste de permissão foi bloqueado fora da árvore de documentos de propostas.');
        }
    }
}
