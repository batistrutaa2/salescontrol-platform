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
            ->expectsOutput('Conexão validada: escrita, leitura, integridade, renomeação e exclusão concluídas.')
            ->assertSuccessful();

        $this->assertSame([], Storage::disk('documentos_test')->allFiles());
    }
}
