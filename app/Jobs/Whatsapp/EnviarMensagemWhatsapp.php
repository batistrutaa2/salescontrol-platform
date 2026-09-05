<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\NovaMensagemWhatsapp;
use App\Jobs\Concerns\RunsTenantFailureCallback;
use App\Jobs\Concerns\UsesWhatsappTenantContext;
use App\Models\WhatsappMensagem;
use App\Services\Evolution\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EnviarMensagemWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, RunsTenantFailureCallback, SerializesModels, UsesWhatsappTenantContext;

    public int $tries = 5;

    public array $backoff = [5, 15, 30, 60, 120];

    public function __construct(public int $mensagemId)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(EvolutionApiService $evolution): void
    {
        $mensagem = WhatsappMensagem::with('conversa.instancia')->find($this->mensagemId);

        if (! $mensagem || $mensagem->status_envio !== 'PENDENTE') {
            return;
        }

        $conversa = $mensagem->conversa;
        $instancia = $conversa->instancia;
        $numero = $conversa->numero;
        $instanceName = $instancia->instance_name;

        $resposta = match ($mensagem->tipo) {
            'text' => $evolution->sendText($instanceName, $numero, (string) $mensagem->body, $mensagem->quoted_message_id),
            'image' => $evolution->sendMedia($instanceName, $numero, 'image', $this->mediaBase64($mensagem), $mensagem->body, null, $mensagem->media_mime),
            'video' => $evolution->sendMedia($instanceName, $numero, 'video', $this->mediaBase64($mensagem), $mensagem->body, null, $mensagem->media_mime),
            'document' => $evolution->sendMedia($instanceName, $numero, 'document', $this->mediaBase64($mensagem), $mensagem->body, basename((string) $mensagem->media_path), $mensagem->media_mime),
            'ptt', 'audio' => $evolution->sendAudio($instanceName, $numero, $this->mediaBase64($mensagem)),
            'sticker' => $evolution->sendSticker($instanceName, $numero, $this->mediaBase64($mensagem)),
            default => throw new \RuntimeException("Tipo de mensagem não suportado para envio: {$mensagem->tipo}"),
        };

        $messageIdEvolution = data_get($resposta, 'key.id');

        $mensagem->update([
            'message_id' => $messageIdEvolution ?: $mensagem->message_id,
            'status_envio' => 'ENVIADA',
            'ack' => max($mensagem->ack, 1),
        ]);

        broadcast(new NovaMensagemWhatsapp($mensagem->fresh(), $conversa->user_id));
    }

    public function failed(\Throwable $exception): void
    {
        $this->runTenantFailureCallback(WhatsappMensagem::class, $this->mensagemId, function () use ($exception): void {
            $mensagem = WhatsappMensagem::with('conversa')->find($this->mensagemId);

            if (! $mensagem) {
                return;
            }

            Log::error('Whatsapp: falha definitiva no envio de mensagem', [
                'mensagem_id' => $mensagem->id,
                'erro' => $exception->getMessage(),
            ]);

            $mensagem->update([
                'status_envio' => 'ERRO',
                'erro_envio' => 'Não foi possível enviar a mensagem pelo provedor.',
            ]);

            broadcast(new NovaMensagemWhatsapp($mensagem->fresh(), $mensagem->conversa->user_id));
        });
    }

    private function mediaBase64(WhatsappMensagem $mensagem): string
    {
        if (! $mensagem->media_path) {
            throw new \RuntimeException('Mensagem de mídia sem arquivo associado.');
        }

        return base64_encode(Storage::disk('public')->get($mensagem->media_path));
    }
}
