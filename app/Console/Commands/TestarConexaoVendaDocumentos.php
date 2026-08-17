<?php

namespace App\Console\Commands;

use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TestarConexaoVendaDocumentos extends Command
{
    protected $signature = 'documentos:testar-conexao';

    protected $description = 'Valida operações pela identidade SFTP; não substitui o teste de acesso pelo Samba';

    public function handle(VendaDocumentoPermissionPolicy $permissions): int
    {
        $diskName = config('documentos.disk');
        $disk = null;
        $directory = trim((string) config('documentos.root'), '/')
            .'/.salescontrol-healthcheck-'.Str::uuid();
        $source = $directory.'/origem.txt';
        $destination = $directory.'/renomeado.txt';
        $contents = 'salescontrol-healthcheck:'.Str::random(48);

        try {
            $permissions->assertConfiguredSftpIdentity();
            $disk = Storage::disk($diskName);
            if (! $disk->put($source, $contents, [
                'visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
            ])) {
                throw new RuntimeException('O servidor recusou a criação do arquivo de teste.');
            }
            if (! $disk->exists($source) || ! hash_equals(hash('sha256', $contents), hash('sha256', $disk->get($source)))) {
                throw new RuntimeException('A leitura ou a conferência de integridade falhou.');
            }
            if (! $disk->move($source, $destination) || ! $disk->exists($destination)) {
                throw new RuntimeException('O servidor não concluiu a renomeação do arquivo de teste.');
            }
            $permissions->applyToFile($disk, $destination);
            if (! $disk->delete($destination) || $disk->exists($destination)) {
                throw new RuntimeException('O servidor não concluiu a exclusão do arquivo de teste.');
            }

            $this->info('SFTP validado: escrita, leitura, integridade, renomeação e exclusão concluídas.');
            $this->warn('A identidade Samba/Windows não foi validada por este comando; confirme grupo, ACL e abertura pela unidade mapeada.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Falha na conexão com o servidor de documentos: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($disk !== null) {
                try {
                    $disk->delete([$source, $destination]);
                    $disk->deleteDirectory($directory);
                } catch (Throwable) {
                    $this->warn('Não foi possível remover automaticamente todos os resíduos do teste.');
                }
            }
        }
    }
}
