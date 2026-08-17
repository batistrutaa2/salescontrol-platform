<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestarConexaoVendaDocumentosTest extends TestCase
{
    public function test_valida_operacoes_e_remove_os_arquivos_temporarios(): void
    {
        Storage::fake('documentos_test');
        config(['documentos.disk' => 'documentos_test']);

        $this->artisan('documentos:testar-conexao')
            ->expectsOutput('SFTP validado: escrita, leitura, integridade, renomeação e exclusão concluídas.')
            ->expectsOutput('A identidade Samba/Windows não foi validada por este comando; confirme grupo, ACL e abertura pela unidade mapeada.')
            ->assertSuccessful();

        $this->assertSame([], Storage::disk('documentos_test')->allFiles());
    }

    public function test_bloqueia_identidade_vazia_antes_de_abrir_a_conexao_sftp(): void
    {
        config([
            'documentos.disk' => 'documentos_test',
            'filesystems.disks.documentos_test' => [
                'driver' => 'sftp',
                'username' => '',
            ],
        ]);

        $this->artisan('documentos:testar-conexao')
            ->expectsOutputToContain('Escrita documental bloqueada: DOCUMENTOS_SFTP_USERNAME não está configurado')
            ->assertFailed();
    }
}
