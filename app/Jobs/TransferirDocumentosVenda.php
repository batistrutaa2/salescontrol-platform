<?php

namespace App\Jobs;

use App\Events\VendaDocumentoAtualizado;
use App\Models\VendaDocumento;
use App\Models\Vendas;
use App\Services\Documentos\DocumentoStatusService;
use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class TransferirDocumentosVenda implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 600;

    public array $backoff = [30, 120, 300, 900];

    public function __construct(public int $vendaId)
    {
        $this->onQueue('documentos-transfer');
    }

    public function handle(DocumentoStatusService $status, VendaDocumentoPermissionPolicy $permissions): void
    {
        $permissions->assertConfiguredSftpIdentity();

        $lock = Cache::lock("documentos:transferir-venda:{$this->vendaId}", 660);
        if (! $lock->get()) {
            return;
        }

        try {
            $venda = Vendas::findOrFail($this->vendaId);
            $venda->documentos()->whereNull('deleted_at')->where('status', 'ENVIANDO')
                ->update(['status' => 'AGUARDANDO_ENVIO']);
            $documentos = $venda->documentos()->whereNull('deleted_at')
                ->where('status', 'AGUARDANDO_ENVIO')->orderBy('id')->limit(10)->get();
            if ($documentos->isEmpty()) {
                return;
            }

            // FilesystemManager mantém este adapter e sua sessão phpseclib no worker persistente.
            $remoto = Storage::disk(config('documentos.disk'));
            $local = Storage::disk('local');

            foreach ($documentos as $doc) {
                $doc->update([
                    'status' => 'ENVIANDO',
                    'tentativas' => $doc->tentativas + 1,
                    'processamento_iniciado_em' => $doc->processamento_iniciado_em ?? now(),
                    'ultima_tentativa_em' => now(),
                    'erro' => null,
                ]);
                event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));

                if (! $doc->caminho_temporario || ! $local->exists($doc->caminho_temporario)) {
                    throw new RuntimeException("A cópia temporária de {$doc->nome_original} não foi encontrada.");
                }

                if ($remoto->fileExists($doc->caminho_remoto)) {
                    if ($remoto->size($doc->caminho_remoto) === $doc->tamanho) {
                        $permissions->applyToFile($remoto, $doc->caminho_remoto);
                        $this->disponibilizar($doc);

                        continue;
                    }
                    throw new RuntimeException("Já existe um arquivo divergente no destino de {$doc->nome_original}.");
                }

                $parcial = $doc->caminho_remoto.'.part-'.$doc->id;
                if ($remoto->fileExists($parcial)) {
                    $remoto->delete($parcial);
                }
                $stream = $local->readStream($doc->caminho_temporario);
                try {
                    if (! is_resource($stream) || ! $remoto->put($parcial, $stream, [
                        'visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
                    ])) {
                        throw new RuntimeException("Falha ao transferir {$doc->nome_original}.");
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                if ($remoto->size($parcial) !== $doc->tamanho) {
                    $remoto->delete($parcial);
                    throw new RuntimeException("O tamanho remoto de {$doc->nome_original} diverge do original.");
                }
                $remoto->move($parcial, $doc->caminho_remoto);
                $permissions->applyToFile($remoto, $doc->caminho_remoto);
                $this->disponibilizar($doc);
            }

            $status->atualizarVenda($venda);
            Cache::forever('documentos:sftp:ultimo_sucesso_em', now()->toIso8601String());
            if ($venda->documentos()->where('status', 'AGUARDANDO_ENVIO')->exists()) {
                self::dispatch($venda->id);
            }
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        $venda = Vendas::find($this->vendaId);
        if (! $venda) {
            return;
        }
        $doc = $venda->documentos()->whereIn('status', ['AGUARDANDO_ENVIO', 'ENVIANDO'])->orderBy('id')->first();
        if ($doc) {
            $doc->update(['status' => 'FALHA', 'erro' => mb_substr($exception->getMessage(), 0, 1000), 'expira_em' => now()->addDays(7)]);
            event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
        }
        app(DocumentoStatusService::class)->atualizarVenda($venda);
        if ($venda->documentos()->where('status', 'AGUARDANDO_ENVIO')->exists()) {
            self::dispatch($venda->id);
        }
    }

    private function disponibilizar(VendaDocumento $doc): void
    {
        $doc->update(['status' => 'DISPONIVEL', 'erro' => null, 'enviado_em' => now(), 'expira_em' => now()->addDay()]);
        event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
    }
}
