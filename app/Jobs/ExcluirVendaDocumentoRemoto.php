<?php

namespace App\Jobs;

use App\Events\VendaDocumentoAtualizado;
use App\Models\VendaDocumento;
use App\Services\Documentos\DocumentoStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExcluirVendaDocumentoRemoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;
    public array $backoff = [30, 120, 300, 900];

    public function __construct(public int $documentoId)
    {
        $this->onQueue('documentos-transfer');
    }

    public function handle(DocumentoStatusService $status): void
    {
        $doc = VendaDocumento::with('venda')->findOrFail($this->documentoId);
        Storage::disk(config('documentos.disk'))->delete($doc->caminho_remoto);
        Storage::disk('local')->delete($doc->caminho_temporario);
        $doc->update(['status' => 'EXCLUIDO', 'erro' => null, 'deleted_at' => now()]);
        $status->atualizarVenda($doc->venda);
        event(new VendaDocumentoAtualizado($doc->venda_id, $doc->empresa_id));
    }
}
