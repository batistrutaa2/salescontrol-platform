<?php

namespace App\Console\Commands;

use App\Models\Vendas;
use App\Services\Documentos\NomeDocumentoService;
use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class RealinharDiretorioVendaDocumentos extends Command
{
    protected $signature = 'documentos:realinhar-venda {venda : ID da venda}';

    protected $description = 'Move documentos existentes para a pasta mapeada da operadora e atualiza os caminhos da venda';

    public function handle(NomeDocumentoService $nomes, VendaDocumentoPermissionPolicy $permissions): int
    {
        $venda = Vendas::with(['operadoraRelation', 'documentos'])->find((int) $this->argument('venda'));
        if (! $venda) {
            $this->error('Venda não encontrada.');

            return self::FAILURE;
        }
        $pastaOperadora = $venda->operadoraRelation?->diretorio_documentos;
        if (! $pastaOperadora) {
            $this->error('A operadora ainda não possui uma pasta de documentos vinculada.');

            return self::FAILURE;
        }

        $origem = $venda->documentacao_diretorio;
        $destino = trim(config('documentos.root'), '/').'/'.$nomes->segmento($pastaOperadora, 'Sem operadora')
            .'/'.$nomes->segmento((string) $venda->nome_contrato, "Venda {$venda->id}");
        if (! $origem || $origem === $destino) {
            $this->info('A venda já está alinhada com a pasta configurada.');

            return self::SUCCESS;
        }
        if (Vendas::where('empresa_id', $venda->empresa_id)->where('documentacao_diretorio', $destino)->whereKeyNot($venda->id)->exists()) {
            $this->error('Outra venda já utiliza o diretório de destino; o realinhamento foi cancelado.');

            return self::FAILURE;
        }

        return Cache::lock("venda-documentos-realinhar:{$venda->id}", 60)->block(10, function () use ($venda, $origem, $destino, $permissions) {
            $disk = Storage::disk(config('documentos.disk'));
            $documentos = $venda->documentos->whereNull('deleted_at');
            $disponiveis = $documentos->where('status', 'DISPONIVEL');
            $movidos = [];

            try {
                foreach ($disponiveis as $documento) {
                    $arquivoDestino = $destino.'/'.$documento->nome_remoto;
                    if (! $disk->exists($documento->caminho_remoto)) {
                        throw new RuntimeException("O arquivo remoto do documento {$documento->id} não foi encontrado na origem.");
                    }
                    if ($disk->exists($arquivoDestino)) {
                        throw new RuntimeException("Já existe um arquivo no destino para o documento {$documento->id}; nada foi movido.");
                    }
                }

                if (! $disk->directoryExists($destino) && ! $disk->makeDirectory($destino)) {
                    throw new RuntimeException('Não foi possível criar o diretório de destino.');
                }
                foreach ($disponiveis as $documento) {
                    $arquivoDestino = $destino.'/'.$documento->nome_remoto;
                    if (! $disk->move($documento->caminho_remoto, $arquivoDestino)) {
                        throw new RuntimeException("Não foi possível mover o documento {$documento->id}.");
                    }
                    $permissions->applyToFile($disk, $arquivoDestino);
                    $movidos[] = [$documento->caminho_remoto, $arquivoDestino];
                }

                DB::transaction(function () use ($venda, $documentos, $destino) {
                    $venda->update(['documentacao_diretorio' => $destino]);
                    foreach ($documentos as $documento) {
                        $documento->update([
                            'diretorio_remoto' => $destino,
                            'caminho_remoto' => $destino.'/'.$documento->nome_remoto,
                        ]);
                    }
                });

                $this->info("Venda realinhada de {$origem} para {$destino}. {$disponiveis->count()} arquivo(s) movido(s).");

                return self::SUCCESS;
            } catch (Throwable $exception) {
                foreach (array_reverse($movidos) as [$arquivoOrigem, $arquivoDestino]) {
                    try {
                        if ($disk->exists($arquivoDestino) && ! $disk->exists($arquivoOrigem)) {
                            $disk->move($arquivoDestino, $arquivoOrigem);
                        }
                    } catch (Throwable) {
                        // O erro principal será exibido; resíduos devem ser auditados manualmente.
                    }
                }
                $this->error('Realinhamento cancelado: '.$exception->getMessage());

                return self::FAILURE;
            }
        });
    }
}
