<?php

namespace App\Console\Commands;

use App\Models\VendaDocumento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LimparVendaDocumentosTemporarios extends Command
{
    protected $signature = 'documentos:limpar-temporarios';
    protected $description = 'Remove cópias temporárias já enviadas ou expiradas';

    public function handle(): int
    {
        VendaDocumento::whereNotNull('expira_em')->where('expira_em', '<=', now())
            ->whereIn('status', ['DISPONIVEL', 'FALHA', 'BLOQUEADO', 'EXCLUIDO'])
            ->chunkById(100, function ($documentos) {
                foreach ($documentos as $documento) {
                    Storage::disk('local')->delete($documento->caminho_temporario);
                    $documento->update(['caminho_temporario' => '']);
                }
            });

        return self::SUCCESS;
    }
}
