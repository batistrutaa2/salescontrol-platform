<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\ConversaWhatsappAtualizada;
use App\Events\Whatsapp\NovaMensagemWhatsapp;
use App\Jobs\Concerns\UsesWhatsappTenantContext;
use App\Models\WhatsappInstancia;
use App\Models\WhatsappMensagem;
use App\Services\Evolution\EvolutionApiService;
use App\Services\Whatsapp\ConversaService;
use App\Services\Whatsapp\MediaStorageService;
use App\Services\Whatsapp\MessagePayloadParser;
use App\Services\Whatsapp\PhoneMatcher;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarMensagemRecebida implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesWhatsappTenantContext;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 300, 600];

    public function __construct(
        public int $instanciaId,
        public array $payload
    ) {
        $this->onQueue('whatsapp');
    }

    public function uniqueId(): string
    {
        return $this->instanciaId.':'.data_get($this->payload, 'data.key.id', uniqid());
    }

    public function handle(
        ConversaService $conversaService,
        MediaStorageService $mediaStorage,
        EvolutionApiService $evolution
    ): void {
        $data = $this->payload['data'] ?? [];
        $key = $data['key'] ?? [];
        $remoteJid = PhoneMatcher::jidEfetivo($key);
        $messageId = $key['id'] ?? null;

        if (! $remoteJid || ! $messageId) {
            return;
        }

        if (PhoneMatcher::isGrupo($remoteJid) || PhoneMatcher::isBroadcast($remoteJid)) {
            return;
        }

        $instancia = WhatsappInstancia::find($this->instanciaId);

        if (! $instancia) {
            return;
        }

        $fromMe = (bool) ($key['fromMe'] ?? false);
        $pushName = $fromMe ? null : ($data['pushName'] ?? null);

        $conversa = $conversaService->resolverConversa($instancia, $remoteJid, $pushName);

        // Mensagem já registrada (ex: enviada pelo CRM e gravada no envio)
        if (WhatsappMensagem::where('conversa_id', $conversa->id)->where('message_id', $messageId)->exists()) {
            return;
        }

        $parsed = MessagePayloadParser::parse($data);

        [$mediaPath, $mediaSize] = $this->resolverMidia($parsed, $instancia, $conversa->empresa_id, $conversa->id, $messageId, $evolution, $mediaStorage);

        $timestamp = isset($data['messageTimestamp'])
          ? Carbon::createFromTimestamp((int) $data['messageTimestamp'])
          : now();

        try {
            $mensagem = WhatsappMensagem::create([
                'empresa_id' => $conversa->empresa_id,
                'conversa_id' => $conversa->id,
                'message_id' => $messageId,
                'direcao' => $fromMe ? 'OUT' : 'IN',
                'tipo' => $parsed['tipo'],
                'body' => $parsed['body'],
                'media_path' => $mediaPath,
                'media_mime' => $parsed['mime'],
                'media_size' => $mediaSize,
                'quoted_message_id' => data_get($data, 'contextInfo.stanzaId')
                  ?? data_get($data, 'message.extendedTextMessage.contextInfo.stanzaId'),
                'ack' => $fromMe ? 1 : 0,
                'status_envio' => $fromMe ? 'ENVIADA' : null,
                'message_timestamp' => $timestamp,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Webhook reentregue — mensagem já processada em corrida paralela
            return;
        }

        $conversa->update([
            'last_message_at' => $timestamp,
            'last_message_preview' => MessagePayloadParser::preview($parsed),
            'unread_count' => $fromMe ? $conversa->unread_count : $conversa->unread_count + 1,
        ]);

        broadcast(new NovaMensagemWhatsapp($mensagem, $conversa->user_id));
        broadcast(new ConversaWhatsappAtualizada($conversa->fresh()));
    }

    /**
     * Salva a mídia (base64 do webhook ou fallback via API).
     * Falha de mídia não bloqueia o registro da mensagem.
     */
    private function resolverMidia(
        array $parsed,
        WhatsappInstancia $instancia,
        int $empresaId,
        int $conversaId,
        string $messageId,
        EvolutionApiService $evolution,
        MediaStorageService $mediaStorage
    ): array {
        if (in_array($parsed['tipo'], ['text', 'location', 'contact', 'unknown'], true)) {
            return [null, null];
        }

        try {
            $base64 = $parsed['base64'];

            if (! $base64) {
                $resposta = $evolution->getBase64FromMediaMessage($instancia->instance_name, $messageId);
                $base64 = $resposta['base64'] ?? null;
            }

            if ($base64) {
                return $mediaStorage->salvarBase64($base64, $parsed['mime'] ?? 'application/octet-stream', $empresaId, $conversaId, $parsed['file_name']);
            }
        } catch (\Throwable $e) {
            Log::warning('Whatsapp: falha ao salvar mídia, mensagem registrada sem media_path', [
                'instancia_id' => $instancia->id,
                'message_id' => $messageId,
                'erro' => $e->getMessage(),
            ]);
        }

        return [null, null];
    }
}
