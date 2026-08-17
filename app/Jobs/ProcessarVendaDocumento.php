<?php

namespace App\Jobs;

use App\Models\VendaDocumento;
use App\Models\Vendas;
use App\Services\Documentos\ClamAvService;
use App\Services\Documentos\DocumentoInfectadoException;
use App\Services\Documentos\NomeDocumentoService;
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

class ProcessarVendaDocumento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 300;

    public array $backoff = [30, 120, 300, 900];

    public function __construct(public int $documentoId)
    {
        $this->onQueue('documentos');
    }

    public function handle(
        ClamAvService $clamAv,
        NomeDocumentoService $nomes,
        VendaDocumentoPermissionPolicy $permissions
    ): void {
        $doc = VendaDocumento::with('venda')->findOrFail($this->documentoId);
        // Compatibilidade para jobs "documentos" enfileirados antes da divisão do pipeline.
        if (in_array($doc->status, ['AGUARDANDO', 'RECEBIDO'], true)) {
            if ($doc->status === 'AGUARDANDO') {
                $doc->update(['status' => 'RECEBIDO']);
            }
            VerificarVendaDocumento::dispatch($doc->id);

            return;
        }
        if (in_array($doc->status, ['VERIFICANDO', 'AGUARDANDO_ENVIO', 'ENVIANDO', 'EXCLUSAO_PENDENTE'], true)) {
            return;
        }
        if (in_array($doc->status, ['DISPONIVEL', 'BLOQUEADO', 'EXCLUIDO'], true)) {
            return;
        }

        $local = Storage::disk('local');
        if (! $local->exists($doc->caminho_temporario)) {
            throw new RuntimeException('A cópia temporária do documento não foi encontrada.');
        }

        $doc->update(['status' => 'VERIFICANDO', 'tentativas' => $doc->tentativas + 1, 'erro' => null]);
        try {
            $clamAv->scan($local->path($doc->caminho_temporario));
        } catch (DocumentoInfectadoException $e) {
            $local->delete($doc->caminho_temporario);
            $doc->update(['status' => 'BLOQUEADO', 'erro' => $e->getMessage(), 'expira_em' => now()]);
            $this->atualizarResumo($doc->venda);

            return;
        }

        $doc->update(['status' => 'ENVIANDO']);
        $this->ajustarPastaManual($doc, $nomes);
        $doc->refresh();
        $permissions->assertConfiguredSftpIdentity();
        $remoto = Storage::disk(config('documentos.disk'));
        $parcial = $doc->caminho_remoto.'.part-'.$doc->id;
        $stream = $local->readStream($doc->caminho_temporario);
        if (! is_resource($stream) || ! $remoto->put($parcial, $stream, [
            'visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
        ])) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw new RuntimeException('Falha ao enviar o documento ao servidor de arquivos.');
        }
        if (is_resource($stream)) {
            fclose($stream);
        }

        try {
            if ($remoto->size($parcial) !== $doc->tamanho) {
                throw new RuntimeException('O tamanho recebido no servidor de arquivos é diferente do original.');
            }
            $streamRemoto = $remoto->readStream($parcial);
            $hashRemoto = is_resource($streamRemoto) ? hash('sha256', stream_get_contents($streamRemoto)) : null;
            if (is_resource($streamRemoto)) {
                fclose($streamRemoto);
            }
            if (! hash_equals($doc->sha256, (string) $hashRemoto)) {
                throw new RuntimeException('A conferência de integridade do documento falhou.');
            }
            if ($remoto->exists($doc->caminho_remoto)) {
                throw new RuntimeException('Já existe um arquivo com o mesmo nome no destino.');
            }
            $remoto->move($parcial, $doc->caminho_remoto);
            $permissions->applyToFile($remoto, $doc->caminho_remoto);
        } catch (Throwable $e) {
            $remoto->delete($parcial);
            throw $e;
        }

        $doc->update([
            'status' => 'DISPONIVEL', 'erro' => null, 'enviado_em' => now(), 'expira_em' => now()->addDays(7),
        ]);
        $this->atualizarResumo($doc->venda);
    }

    public function failed(Throwable $exception): void
    {
        $doc = VendaDocumento::with('venda')->find($this->documentoId);
        if (! $doc || in_array($doc->status, ['DISPONIVEL', 'BLOQUEADO', 'EXCLUIDO'], true)) {
            return;
        }
        $doc->update(['status' => 'FALHA', 'erro' => mb_substr($exception->getMessage(), 0, 1000), 'expira_em' => now()->addDays(30)]);
        $this->atualizarResumo($doc->venda);
    }

    private function ajustarPastaManual(VendaDocumento $doc, NomeDocumentoService $nomes): void
    {
        Cache::lock("venda-documentos-diretorio:{$doc->venda_id}", 30)->block(10, function () use ($doc) {
            $venda = Vendas::withCount(['documentos as enviados_count' => fn ($q) => $q->where('status', 'DISPONIVEL')])->findOrFail($doc->venda_id);
            if ($venda->enviados_count > 0) {
                return;
            }
            $disk = Storage::disk(config('documentos.disk'));
            if (! $disk->directoryExists($venda->documentacao_diretorio)) {
                return;
            }

            $base = preg_replace('/ - Venda \d+$/u', '', $venda->documentacao_diretorio);
            $numero = 2;
            do {
                $novo = "{$base} - Venda {$numero}";
                $numero++;
            } while ($disk->directoryExists($novo) || Vendas::where('empresa_id', $venda->empresa_id)->where('documentacao_diretorio', $novo)->whereKeyNot($venda->id)->exists());

            $venda->update(['documentacao_diretorio' => $novo]);
            foreach ($venda->documentos()->whereNotIn('status', ['DISPONIVEL', 'EXCLUIDO'])->get() as $item) {
                $item->update(['diretorio_remoto' => $novo, 'caminho_remoto' => "{$novo}/{$item->nome_remoto}"]);
            }
        });
    }

    private function atualizarResumo(Vendas $venda): void
    {
        $status = $venda->documentos()->whereNull('deleted_at')->pluck('status');
        $resumo = $status->contains('FALHA') || $status->contains('BLOQUEADO') ? 'COM_FALHA'
            : ($status->isNotEmpty() && $status->every(fn ($item) => $item === 'DISPONIVEL') ? 'DISPONIVEL' : 'PROCESSANDO');
        $venda->update(['documentacao_status' => $resumo]);
    }
}
