<?php

namespace App\Console\Commands;

use App\Models\WhatsappMensagem;
use App\Services\Evolution\EvolutionApiService;
use App\Services\Whatsapp\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsappRedownloadMedia extends Command
{
    protected $signature = 'whatsapp:redownload-media {--limite=50 : Máximo de mensagens por execução}';

    protected $description = 'Rebaixa mídias de mensagens que ficaram sem arquivo (media_path nulo)';

    public function handle(EvolutionApiService $evolution, MediaStorageService $mediaStorage): int
    {
        $pendentes = WhatsappMensagem::with('conversa.instancia')
            ->whereNull('media_path')
            ->whereIn('tipo', ['image', 'video', 'audio', 'ptt', 'sticker', 'document'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('id')
            ->limit((int) $this->option('limite'))
            ->get();

        $recuperadas = 0;

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

        $this->info("Mídias recuperadas: {$recuperadas} de {$pendentes->count()} pendentes.");

        return self::SUCCESS;
    }
}
