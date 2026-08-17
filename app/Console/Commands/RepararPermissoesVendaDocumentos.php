<?php

namespace App\Console\Commands;

use App\Models\VendaDocumento;
use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RepararPermissoesVendaDocumentos extends Command
{
    protected $signature = 'documentos:reparar-permissoes
        {--apply : Aplica o modo 0660 aos arquivos remotos encontrados}
        {--limit=5000 : Quantidade máxima de registros por execução}';

    protected $description = 'Audita ou reaplica a permissão colaborativa nos documentos remotos de propostas';

    public function handle(VendaDocumentoPermissionPolicy $permissions): int
    {
        $root = trim((string) config('documentos.root'), '/');
        $limit = max(1, min(50000, (int) $this->option('limit')));
        $query = VendaDocumento::query()
            ->whereNull('deleted_at')
            ->whereIn('status', ['DISPONIVEL', 'EXCLUSAO_PENDENTE'])
            ->where('caminho_remoto', 'like', $root.'/%')
            ->orderBy('id')
            ->limit($limit);

        $count = min($limit, (clone $query)->reorder()->count());
        if (! $this->option('apply')) {
            $this->info("Auditoria concluída: {$count} arquivo(s) elegível(is). Use --apply para reaplicar 0660.");

            return self::SUCCESS;
        }

        $permissions->assertConfiguredSftpIdentity();
        $disk = Storage::disk(config('documentos.disk'));
        $updated = 0;
        $missing = 0;
        $failed = 0;

        foreach ($query->cursor() as $document) {
            try {
                if (! $disk->fileExists($document->caminho_remoto)) {
                    $missing++;

                    continue;
                }

                $permissions->applyToFile($disk, $document->caminho_remoto);
                $updated++;
            } catch (Throwable $exception) {
                $failed++;
                $this->warn("Documento {$document->id}: não foi possível reaplicar a permissão.");
            }
        }

        $this->info("Reparo concluído: {$updated} ajustado(s), {$missing} ausente(s), {$failed} falha(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
