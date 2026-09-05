<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\WhatsappMensagem;
use App\Services\Evolution\EvolutionApiService;
use App\Services\Whatsapp\MediaStorageService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsappRedownloadMedia extends Command
{
    protected $signature = 'whatsapp:redownload-media {--limite=50 : Máximo de mensagens por execução} {--empresa= : Limita a uma empresa específica}';

    protected $description = 'Rebaixa mídias de mensagens que ficaram sem arquivo (media_path nulo)';

    public function handle(EvolutionApiService $evolution, MediaStorageService $mediaStorage): int
    {
        $limite = (int) $this->option('limite');
        if ($limite < 1 || $limite > 1000) {
            $this->error('Limite inválido. Informe um valor entre 1 e 1000.');

            return self::FAILURE;
        }

        $empresas = $this->empresasSelecionadas();
        if ($empresas === null) {
            return self::FAILURE;
        }

        $recuperadas = 0;
        $processadas = 0;
        $context = app(TenantContext::class);
        $context->clear();

        try {
            foreach ($empresas as $empresaId) {
                $restantes = $limite - $processadas;
                if ($restantes <= 0) {
                    break;
                }

                $context->run($empresaId, function () use ($evolution, $mediaStorage, $restantes, &$recuperadas, &$processadas): void {
                    $pendentes = WhatsappMensagem::with('conversa.instancia')
                        ->whereNull('media_path')
                        ->whereIn('tipo', ['image', 'video', 'audio', 'ptt', 'sticker', 'document'])
                        ->where('created_at', '>=', now()->subDays(7))
                        ->orderByDesc('id')
                        ->limit($restantes)
                        ->get();

                    $processadas += $pendentes->count();

                    foreach ($pendentes as $mensagem) {
                        $instancia = $mensagem->conversa?->instancia;

                        if (! $instancia) {
                            continue;
                        }

                        try {
                            $resposta = $evolution->getBase64FromMediaMessage($instancia->instance_name, $mensagem->message_id);
                            $base64 = $resposta['base64'] ?? null;

                            if (! $base64) {
                                continue;
                            }

                            [$path, $size] = $mediaStorage->salvarBase64(
                                $base64,
                                $mensagem->media_mime ?? ($resposta['mimetype'] ?? 'application/octet-stream'),
                                $mensagem->empresa_id,
                                $mensagem->conversa_id
                            );

                            $mensagem->update(['media_path' => $path, 'media_size' => $size]);
                            $recuperadas++;
                        } catch (\Throwable $e) {
                            Log::debug('whatsapp:redownload-media — mídia indisponível', [
                                'mensagem_id' => $mensagem->id,
                                'erro' => $e->getMessage(),
                            ]);
                        }
                    }
                });
            }
        } finally {
            $context->clear();
        }

        $this->info("Mídias recuperadas: {$recuperadas} de {$processadas} pendentes.");

        return self::SUCCESS;
    }

    private function empresasSelecionadas(): ?array
    {
        $empresaId = filter_var($this->option('empresa'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('empresa') !== null && $empresaId === false) {
            $this->error('Empresa inválida.');

            return null;
        }

        $ids = Empresa::query()
            ->when($empresaId, fn ($query) => $query->whereKey($empresaId))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($empresaId && $ids === []) {
            $this->error('Empresa inválida.');

            return null;
        }

        return $ids;
    }
}
