<?php

namespace App\Console\Commands;

use App\Jobs\TransferirDocumentosVenda;
use App\Models\VendaDocumento;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessarVendaDocumentosPendentes extends Command
{
    protected $signature = 'documentos:processar-pendentes
        {empresa_id : ID da empresa cujos documentos serão processados}
        {--limit=500}';

    protected $description = 'Enfileira documentos aguardando após a ativação da integração SFTP';

    public function handle(): int
    {
        if (! config('documentos.processamento_ativo')) {
            $this->error('Defina DOCUMENTOS_PROCESSAMENTO_ATIVO=true antes de processar os documentos.');

            return self::FAILURE;
        }

        $empresaId = (int) $this->argument('empresa_id');
        if (! DB::table('empresas')->where('id', $empresaId)->exists()) {
            $this->error('Empresa inválida.');

            return self::FAILURE;
        }

        return app(TenantContext::class)->run($empresaId, fn () => $this->processar());
    }

    private function processar(): int
    {
        $limite = max(1, min(5000, (int) $this->option('limit')));
        $ids = VendaDocumento::where(function ($query) {
            $query->whereIn('status', ['AGUARDANDO', 'RECEBIDO', 'VERIFICANDO'])
                ->orWhere(function ($query) {
                    $query->where('status', 'FALHA')->where('erro', 'like', '%antivírus%');
                });
        })
            ->whereNull('deleted_at')
            ->where('caminho_temporario', '<>', '')
            ->orderBy('id')
            ->limit($limite)
            ->pluck('id');

        VendaDocumento::whereIn('id', $ids)->update([
            'status' => 'AGUARDANDO_ENVIO',
            'erro' => null,
        ]);

        $vendas = VendaDocumento::where('status', 'AGUARDANDO_ENVIO')
            ->whereNull('deleted_at')->distinct()->limit($limite)->pluck('venda_id');
        foreach ($vendas as $vendaId) {
            TransferirDocumentosVenda::dispatch($vendaId);
        }

        $this->info("{$ids->count()} documento(s) recuperado(s) e {$vendas->count()} venda(s) para transferência enfileiradas.");

        return self::SUCCESS;
    }
}
