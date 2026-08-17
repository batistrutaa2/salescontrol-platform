<?php

namespace Tests\Unit\Documentos;

use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class VendaDocumentoPermissionPolicyTest extends TestCase
{
    public function test_define_modos_colaborativos_restritos_ao_grupo(): void
    {
        $this->assertSame([
            'file' => ['public' => 0660, 'private' => 0660],
            'dir' => ['public' => 02770, 'private' => 02770],
        ], VendaDocumentoPermissionPolicy::permissionMap());

        $this->assertSame(
            VendaDocumentoPermissionPolicy::permissionMap(),
            config('filesystems.disks.documentos_sftp.permissions')
        );
    }

    public function test_bloqueia_ajuste_fora_da_arvore_de_propostas(): void
    {
        config(['documentos.root' => 'EmAnalise']);

        $this->expectException(InvalidArgumentException::class);
        (new VendaDocumentoPermissionPolicy)->assertProposalDocumentPath('Financeiro/arquivo.pdf');
    }

    public function test_bloqueia_escrita_sftp_com_identidade_root(): void
    {
        config([
            'documentos.disk' => 'documentos_test',
            'filesystems.disks.documentos_test' => [
                'driver' => 'sftp',
                'username' => 'root',
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DOCUMENTOS_SFTP_USERNAME deve ser crm_documentos');

        (new VendaDocumentoPermissionPolicy)->assertConfiguredSftpIdentity();
    }
}
