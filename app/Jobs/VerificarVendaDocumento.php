<?php

namespace App\Jobs;

use App\Events\VendaDocumentoAtualizado;
use App\Models\VendaDocumento;
use App\Services\Documentos\ClamAvService;
use App\Services\Documentos\DocumentoInfectadoException;
use App\Services\Documentos\DocumentoStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class VerificarVendaDocumento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 180;
    public array $backoff = [30, 120, 300, 900];

    public function __construct(public int $documentoId)
    {
        $this->onQueue('documentos-scan');
    }

    public function handle(ClamAvService $clamAv, DocumentoStatusService $status): void
    {
        $doc = VendaDocumento::with('venda')->findOrFail($this->documentoId);
        if (! in_array($doc->status, ['RECEBIDO', 'VERIFICANDO', 'FALHA'], true)) return;

        $local = Storage::disk('local');
        if (! $doc->caminho_temporario || ! $local->exists($doc->caminho_temporario)) {
            throw new RuntimeException('A cópia temporária do documento não foi encontrada.');
        }

        $doc->update([
            'status' => 'VERIFICANDO',
            'tentativas' => $doc->tentativas + 1,
            'erro' => null,
            'processamento_iniciado_em' => $doc->processamento_iniciado_em ?? now(),
            'ultima_tentativa_em' => now(),
        ]);
        event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));

        try {
            $clamAv->scan($local->path($doc->caminho_temporario));
        } catch (DocumentoInfectadoException $e) {
            $local->delete($doc->caminho_temporario);
            $doc->update(['status' => 'BLOQUEADO', 'erro' => $e->getMessage(), 'expira_em' => now()]);
            $status->atualizarVenda($doc->venda);
            event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
            return;
        }

        $doc->update(['status' => 'AGUARDANDO_ENVIO', 'verificado_em' => now(), 'erro' => null]);
        $status->atualizarVenda($doc->venda);
        TransferirDocumentosVenda::dispatch($doc->venda_id);
        event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
    }

    public function failed(Throwable $exception): void
    {
        $doc = VendaDocumento::with('venda')->find($this->documentoId);
        if (! $doc || in_array($doc->status, ['DISPONIVEL', 'BLOQUEADO', 'EXCLUIDO'], true)) return;
        $doc->update(['status' => 'FALHA', 'erro' => mb_substr($exception->getMessage(), 0, 1000), 'expira_em' => now()->addDays(7)]);
        app(DocumentoStatusService::class)->atualizarVenda($doc->venda);
        event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
    }
}
