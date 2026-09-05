<?php

namespace App\Services\Documentos;

use App\Jobs\TransferirDocumentosVenda;
use App\Models\Operadora;
use App\Models\VendaDocumento;
use App\Models\Vendas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistrarVendaDocumentoService
{
    public function __construct(private readonly NomeDocumentoService $nomes) {}

    /**
     * @return array{documento: VendaDocumento, criado: bool}
     */
    public function registrar(Vendas $venda, UploadedFile $arquivo, ?string $clientUploadId, int $uploadedBy): array
    {
        $clientId = trim((string) $clientUploadId) ?: (string) Str::uuid();
        $nomeOriginal = $arquivo->getClientOriginalName();
        $nomeNormalizado = $this->nomes->normalizado($nomeOriginal);
        $sha256 = hash_file('sha256', $arquivo->getRealPath());
        $temporario = null;

        if ($sha256 === false) {
            throw ValidationException::withMessages([
                'arquivo' => 'Não foi possível conferir o conteúdo do documento.',
            ]);
        }

        try {
            return DB::transaction(function () use (
                $venda,
                $arquivo,
                $clientId,
                $nomeOriginal,
                $nomeNormalizado,
                $sha256,
                $uploadedBy,
                &$temporario
            ) {
                $bloqueada = Vendas::whereKey($venda->id)
                    ->where('empresa_id', $venda->empresa_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $existente = VendaDocumento::where('venda_id', $bloqueada->id)
                    ->where('empresa_id', $bloqueada->empresa_id)
                    ->where('client_upload_id', $clientId)
                    ->first();
                if ($existente) {
                    $this->validarRepeticaoIdempotente($existente, $nomeNormalizado, $arquivo, $sha256);

                    return ['documento' => $existente, 'criado' => false];
                }

                $ativos = $bloqueada->documentos()
                    ->whereNull('deleted_at')
                    ->get(['id', 'nome_original']);
                $nomeRepetido = $ativos->contains(function (VendaDocumento $documento) use ($nomeNormalizado) {
                    return $this->nomes->normalizado($documento->nome_original) === $nomeNormalizado;
                });
                if ($nomeRepetido) {
                    throw ValidationException::withMessages([
                        'arquivo' => "Já existe um documento chamado “{$nomeOriginal}” nesta venda. Renomeie o arquivo ou exclua o existente antes de enviar novamente.",
                    ]);
                }

                if ($ativos->count() >= config('documentos.max_files')) {
                    throw ValidationException::withMessages([
                        'arquivo' => 'A venda aceita no máximo 30 documentos.',
                    ]);
                }

                $nomeRemoto = $this->nomeRemotoDisponivel(
                    $bloqueada,
                    $this->nomes->arquivo($nomeOriginal)
                );
                $diretorio = $this->reservarDiretorio($bloqueada);
                $temporario = $arquivo->storeAs(
                    "venda-documentos/{$bloqueada->empresa_id}/{$bloqueada->id}",
                    Str::uuid().'.upload',
                    'local'
                );

                if (! $temporario) {
                    throw ValidationException::withMessages([
                        'arquivo' => 'Não foi possível guardar o arquivo para envio.',
                    ]);
                }

                $documento = VendaDocumento::create([
                    'venda_id' => $bloqueada->id,
                    'empresa_id' => $bloqueada->empresa_id,
                    'uploaded_by' => $uploadedBy,
                    'client_upload_id' => $clientId,
                    'nome_original' => $nomeOriginal,
                    'nome_remoto' => $nomeRemoto,
                    'mime_type' => (string) $arquivo->getMimeType(),
                    'tamanho' => $arquivo->getSize(),
                    'sha256' => $sha256,
                    'caminho_temporario' => $temporario,
                    'diretorio_remoto' => $diretorio,
                    'caminho_remoto' => "{$diretorio}/{$nomeRemoto}",
                    'status' => config('documentos.processamento_ativo') ? 'AGUARDANDO_ENVIO' : 'RECEBIDO',
                ]);

                if (config('documentos.processamento_ativo')) {
                    $bloqueada->update(['documentacao_status' => 'PROCESSANDO']);
                    TransferirDocumentosVenda::dispatch($bloqueada->id)->afterCommit();
                } else {
                    $bloqueada->update(['documentacao_status' => 'PENDENTE']);
                }

                return ['documento' => $documento, 'criado' => true];
            });
        } catch (Throwable $exception) {
            if ($temporario && ! VendaDocumento::where('caminho_temporario', $temporario)->exists()) {
                Storage::disk('local')->delete($temporario);
            }

            throw $exception;
        }
    }

    private function validarRepeticaoIdempotente(
        VendaDocumento $existente,
        string $nomeNormalizado,
        UploadedFile $arquivo,
        string $sha256
    ): void {
        $mesmaRequisicao = $this->nomes->normalizado($existente->nome_original) === $nomeNormalizado
            && $existente->tamanho === $arquivo->getSize()
            && hash_equals($existente->sha256, $sha256);

        if (! $mesmaRequisicao) {
            throw ValidationException::withMessages([
                'client_upload_id' => 'Este identificador de upload já foi usado por outro documento.',
            ]);
        }
    }

    private function reservarDiretorio(Vendas $venda): string
    {
        $diretorioOperadora = Operadora::whereKey($venda->operadora_id)
            ->where('empresa_id', $venda->empresa_id)
            ->value('diretorio_documentos');
        if (! $diretorioOperadora) {
            throw ValidationException::withMessages([
                'arquivo' => "A operadora {$venda->operadora} ainda não está vinculada a uma pasta no servidor de documentos.",
            ]);
        }

        $prefixo = config('documentos.root').'/'.$this->nomes->segmento($diretorioOperadora, 'Sem operadora');
        if ($venda->documentacao_diretorio) {
            if (! str_starts_with($venda->documentacao_diretorio, $prefixo.'/')) {
                $partes = explode('/', $venda->documentacao_diretorio, 3);
                $segmentoVenda = $partes[2] ?? $this->nomes->segmento((string) $venda->nome_contrato, "Venda {$venda->id}");
                $novoDiretorio = $prefixo.'/'.$segmentoVenda;
                if (Vendas::where('empresa_id', $venda->empresa_id)
                    ->where('documentacao_diretorio', $novoDiretorio)
                    ->whereKeyNot($venda->id)
                    ->exists()) {
                    $novoDiretorio .= " - Venda {$venda->id}";
                }

                $venda->documentos()->whereNull('deleted_at')->get()->each(function (VendaDocumento $documento) use ($novoDiretorio) {
                    $documento->update([
                        'diretorio_remoto' => $novoDiretorio,
                        'caminho_remoto' => $novoDiretorio.'/'.$documento->nome_remoto,
                        'status' => $documento->status === 'DISPONIVEL' ? 'FALHA' : $documento->status,
                        'erro' => $documento->status === 'DISPONIVEL'
                            ? 'Documento registrado no diretório anterior. Reenvie para confirmar a cópia na pasta correta.'
                            : $documento->erro,
                    ]);
                });
                $venda->update(['documentacao_diretorio' => $novoDiretorio]);

                return $novoDiretorio;
            }

            return $venda->documentacao_diretorio;
        }

        $base = $prefixo.'/'.$this->nomes->segmento((string) $venda->nome_contrato, "Venda {$venda->id}");
        $diretorio = Vendas::where('empresa_id', $venda->empresa_id)
            ->where('documentacao_diretorio', $base)
            ->exists()
            ? "{$base} - Venda {$venda->id}"
            : $base;
        $venda->update(['documentacao_diretorio' => $diretorio]);

        return $diretorio;
    }

    private function nomeRemotoDisponivel(Vendas $venda, string $nome): string
    {
        $candidato = $nome;
        $numero = 2;
        while ($venda->documentos()->whereNull('deleted_at')->where('nome_remoto', $candidato)->exists()) {
            $candidato = $this->nomes->comSufixo($nome, $numero++);
        }

        return $candidato;
    }
}
