<?php

namespace App\Console\Commands;

use App\Jobs\VerificarVendaDocumento;
use App\Jobs\TransferirDocumentosVenda;
use App\Models\VendaDocumento;
use Illuminate\Console\Command;

class ProcessarVendaDocumentosPendentes extends Command
{
    protected $signature = 'documentos:processar-pendentes {--limit=500}';
    protected $description = 'Enfileira documentos aguardando após a ativação da integração SFTP';

    public function handle(): int
    {
        if (! config('documentos.processamento_ativo')) {
            $this->error('Defina DOCUMENTOS_PROCESSAMENTO_ATIVO=true antes de processar os documentos.');

            return self::FAILURE;
        }

        $limite = max(1, min(5000, (int) $this->option('limit')));
        $ids = VendaDocumento::whereIn('status', ['AGUARDANDO', 'RECEBIDO'])
            ->whereNull('deleted_at')
            ->where('caminho_temporario', '<>', '')
            ->orderBy('id')
            ->limit($limite)
            ->pluck('id');

        foreach ($ids as $id) {
            VerificarVendaDocumento::dispatch($id);
        }

        $vendas = VendaDocumento::where('status', 'AGUARDANDO_ENVIO')
            ->whereNull('deleted_at')->distinct()->limit($limite)->pluck('venda_id');
        foreach ($vendas as $vendaId) {
            TransferirDocumentosVenda::dispatch($vendaId);
        }

        $this->info("{$ids->count()} documento(s) para scan e {$vendas->count()} venda(s) para transferência enfileirados.");

        return self::SUCCESS;
    }
}
