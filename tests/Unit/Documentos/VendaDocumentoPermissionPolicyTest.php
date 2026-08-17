<?php

namespace Tests\Unit\Documentos;

use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use InvalidArgumentException;
use Tests\TestCase;

class VendaDocumentoPermissionPolicyTest extends TestCase
{
    public function test_define_modos_colaborativos_restritos_ao_grupo(): void
    {
        $this->assertSame([
            'file' => ['public' => 0660, 'private' => 0660],
            'dir' => ['public' => 0770, 'private' => 0770],
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
}
