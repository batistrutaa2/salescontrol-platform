<?php

namespace App\Jobs;

use App\Events\VendaDocumentoAtualizado;
use App\Jobs\Concerns\RunsTenantFailureCallback;
use App\Jobs\Concerns\UsesTenantContext;
use App\Models\VendaDocumento;
use App\Services\Documentos\DocumentoStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class VerificarVendaDocumento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, RunsTenantFailureCallback, SerializesModels, UsesTenantContext;

    public int $tries = 5;

    public int $timeout = 180;

    public array $backoff = [30, 120, 300, 900];

    public function __construct(public int $documentoId)
    {
        $this->onQueue('documentos-scan');
    }

    /**
     * Compatibilidade com jobs que já estavam na fila documentos-scan antes
     * da remoção do antivírus do caminho de upload.
     */
    public function handle(DocumentoStatusService $status): void
    {
        $doc = VendaDocumento::with('venda')->findOrFail($this->documentoId);
        if (! in_array($doc->status, ['RECEBIDO', 'VERIFICANDO', 'FALHA'], true)) {
            return;
        }

        $doc->update([
            'status' => 'AGUARDANDO_ENVIO',
            'erro' => null,
            'processamento_iniciado_em' => $doc->processamento_iniciado_em ?? now(),
            'ultima_tentativa_em' => now(),
        ]);
        $status->atualizarVenda($doc->venda);
        TransferirDocumentosVenda::dispatch($doc->venda_id);
        event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
    }

    public function failed(Throwable $exception): void
    {
        $this->runTenantFailureCallback(VendaDocumento::class, $this->documentoId, function () use ($exception): void {
            $doc = VendaDocumento::with('venda')->find($this->documentoId);
            if (! $doc || in_array($doc->status, ['DISPONIVEL', 'BLOQUEADO', 'EXCLUIDO'], true)) {
                return;
            }
            $doc->update(['status' => 'FALHA', 'erro' => mb_substr($exception->getMessage(), 0, 1000), 'expira_em' => now()->addDays(7)]);
            app(DocumentoStatusService::class)->atualizarVenda($doc->venda);
            event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
        });
    }
}
