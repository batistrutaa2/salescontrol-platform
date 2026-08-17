<?php

namespace Tests\Feature;

use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use Tests\TestCase;

class ValidarConfiguracaoVendaDocumentosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'documentos.disk' => 'documentos_test',
            'filesystems.disks.documentos_test' => [
                'driver' => 'sftp',
                'host' => 'servidor-documentos.test',
                'username' => 'crm_documentos',
                'privateKey' => __FILE__,
                'hostFingerprint' => 'SHA256:teste-local',
                'root' => '/srv/samba/administrativo',
                'visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
                'directory_visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
                'permissions' => VendaDocumentoPermissionPolicy::permissionMap(),
            ],
            'filesystems.disks.local.root' => storage_path('framework/testing'),
            'queue.connections.redis.retry_after' => 900,
        ]);
    }

    public function test_aceita_contrato_de_permissoes_colaborativas(): void
    {
        $this->artisan('documentos:validar-configuracao')
            ->expectsOutput('Configuração documental validada sem exposição de credenciais.')
            ->assertSuccessful();
    }

    public function test_rejeita_modos_que_impedem_colaboracao_pelo_samba(): void
    {
        config(['filesystems.disks.documentos_test.permissions.file.private' => 0600]);

        $this->artisan('documentos:validar-configuracao')
            ->expectsOutput('O disco documental precisa criar arquivos 0660 e diretórios 0770.')
            ->assertFailed();
    }
}
