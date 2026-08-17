<?php

namespace App\Services\Documentos;

use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;
use RuntimeException;

final class VendaDocumentoPermissionPolicy
{
    public const VISIBILITY = 'private';

    public const FILE_MODE = 0660;

    /**
     * O bit setgid mantém todos os novos diretórios no grupo compartilhado
     * entre o usuário SFTP e as identidades autorizadas pelo Samba.
     */
    public const DIRECTORY_MODE = 02770;

    public static function permissionMap(): array
    {
        return [
            'file' => ['public' => self::FILE_MODE, 'private' => self::FILE_MODE],
            'dir' => ['public' => self::DIRECTORY_MODE, 'private' => self::DIRECTORY_MODE],
        ];
    }

    public function applyToFile(Filesystem $disk, string $path): void
    {
        $this->assertConfiguredSftpIdentity();
        $this->assertProposalDocumentPath($path);
        if (! $disk->setVisibility($path, self::VISIBILITY)) {
            throw new RuntimeException('Não foi possível aplicar a permissão colaborativa ao documento.');
        }
    }

    /**
     * O login SFTP e as contas Samba são identidades independentes. A
     * colaboração entre elas é garantida no servidor pelo grupo POSIX,
     * setgid e ACL padrão, não por igualdade de nomes de usuário.
     */
    public function assertConfiguredSftpIdentity(): void
    {
        $diskName = (string) config('documentos.disk');
        $disk = config("filesystems.disks.{$diskName}");

        // Discos locais/fakes são usados apenas pelos testes automatizados.
        if (! is_array($disk) || ($disk['driver'] ?? null) !== 'sftp') {
            return;
        }

        if (blank($disk['username'] ?? null)) {
            throw new RuntimeException(
                'Escrita documental bloqueada: DOCUMENTOS_SFTP_USERNAME não está configurado.'
            );
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
