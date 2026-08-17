<?php

namespace App\Console\Commands;

use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use Illuminate\Console\Command;

class ValidarConfiguracaoVendaDocumentos extends Command
{
    protected $signature = 'documentos:validar-configuracao {--production : Exige Redis e processamento ativo}';

    protected $description = 'Valida a configuração do pipeline documental sem revelar credenciais';

    public function handle(): int
    {
        $erros = [];
        $disk = config('filesystems.disks.'.config('documentos.disk'));

        if (! is_array($disk) || ($disk['driver'] ?? null) !== 'sftp') {
            $erros[] = 'O disco documental não usa o driver SFTP.';
        }
        if (! class_exists(\League\Flysystem\PhpseclibV3\SftpConnectionProvider::class)) {
            $erros[] = 'A dependência league/flysystem-sftp-v3 não está instalada.';
        }
        foreach (['host', 'username', 'privateKey', 'hostFingerprint', 'root'] as $campo) {
            if (blank($disk[$campo] ?? null)) {
                $erros[] = "A configuração SFTP {$campo} está vazia.";
            }
        }
        if (($disk['visibility'] ?? null) !== VendaDocumentoPermissionPolicy::VISIBILITY
            || ($disk['directory_visibility'] ?? null) !== VendaDocumentoPermissionPolicy::VISIBILITY
            || ($disk['permissions'] ?? null) !== VendaDocumentoPermissionPolicy::permissionMap()) {
            $erros[] = 'O disco documental precisa criar arquivos 0660 e diretórios 2770 (setgid).';
        }

        $chave = $disk['privateKey'] ?? null;
        if ($chave && (! is_file($chave) || ! is_readable($chave))) {
            $erros[] = 'A chave privada SFTP não existe ou não pode ser lida pelo container.';
        }
        $temporarios = config('filesystems.disks.local.root');
        if (! is_dir($temporarios) || ! is_writable($temporarios)) {
            $erros[] = 'O armazenamento temporário local não pode ser escrito pelo container.';
        }
        if (! extension_loaded('openssl') || ! extension_loaded('sodium') || ! extension_loaded('gmp')) {
            $erros[] = 'As extensões openssl, sodium e gmp precisam estar ativas.';
        }
        if ((int) config('queue.connections.redis.retry_after') <= 600) {
            $erros[] = 'REDIS_QUEUE_RETRY_AFTER deve ser maior que 600 segundos.';
        }

        if ($this->option('production')) {
            if (! config('documentos.processamento_ativo')) {
                $erros[] = 'DOCUMENTOS_PROCESSAMENTO_ATIVO precisa estar habilitado.';
            }
            if (config('queue.default') !== 'redis') {
                $erros[] = 'QUEUE_CONNECTION precisa ser redis em produção.';
            }
            if (config('cache.default') !== 'redis') {
                $erros[] = 'CACHE_STORE precisa ser redis em produção.';
            }
        }

        foreach ($erros as $erro) {
            $this->error($erro);
        }
        if ($erros) {
            return self::FAILURE;
        }

        $this->info('Configuração documental validada sem exposição de credenciais.');

        return self::SUCCESS;
    }
}
