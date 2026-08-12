<?php

namespace App\Services\Documentos;

use RuntimeException;

class ClamAvService
{
    public function scan(string $absolutePath): void
    {
        $host = config('documentos.clamav.host');
        $port = (int) config('documentos.clamav.port');
        $timeout = (float) config('documentos.clamav.timeout');
        $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $erro, $timeout);

        if (! is_resource($socket)) {
            throw new RuntimeException('Antivírus indisponível. O documento será tentado novamente.');
        }

        stream_set_timeout($socket, (int) ceil($timeout));
        $this->writeAll($socket, "zINSTREAM\0");
        $arquivo = fopen($absolutePath, 'rb');
        if (! is_resource($arquivo)) {
            fclose($socket);
            throw new RuntimeException('Não foi possível abrir o documento para verificação.');
        }

        try {
            while (! feof($arquivo)) {
                $bloco = fread($arquivo, 8192);
                if ($bloco === false) {
                    throw new RuntimeException('Falha durante a leitura do documento.');
                }
                if ($bloco !== '') {
                    $this->writeAll($socket, pack('N', strlen($bloco)).$bloco);
                }
            }
            $this->writeAll($socket, pack('N', 0));
            $resposta = stream_get_contents($socket);
            $metadata = stream_get_meta_data($socket);
            if ($resposta === false || ($metadata['timed_out'] ?? false)) {
                throw new RuntimeException('O antivírus não respondeu dentro do tempo esperado.');
            }
        } finally {
            fclose($arquivo);
            fclose($socket);
        }

        if (str_contains((string) $resposta, 'FOUND')) {
            throw new DocumentoInfectadoException('O antivírus bloqueou este arquivo.');
        }
        if (! str_contains((string) $resposta, 'OK')) {
            throw new RuntimeException('O antivírus não conseguiu concluir a verificação.');
        }
    }

    /** @param resource $socket */
    private function writeAll($socket, string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = @fwrite($socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('A conexão com o antivírus foi interrompida. O documento será tentado novamente.');
            }
            $offset += $written;
        }
    }
}
