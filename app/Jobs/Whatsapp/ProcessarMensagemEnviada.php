<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\ConversaWhatsappAtualizada;
use App\Events\Whatsapp\NovaMensagemWhatsapp;
use App\Jobs\Concerns\UsesWhatsappTenantContext;
use App\Models\WhatsappInstancia;
use App\Models\WhatsappMensagem;
use App\Services\Whatsapp\ConversaService;
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

class ProcessarMensagemEnviada implements ShouldBeUnique, ShouldQueue
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
        return 'sent:'.$this->instanciaId.':'.data_get($this->payload, 'data.key.id', uniqid());
    }

    public function handle(ConversaService $conversaService): void
    {
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

        $conversa = $conversaService->resolverConversa($instancia, $remoteJid);

        $existente = WhatsappMensagem::where('conversa_id', $conversa->id)
            ->where('message_id', $messageId)
            ->first();

        if ($existente) {
            // Mensagem enviada pelo CRM — confirma o envio
            if ($existente->status_envio === 'PENDENTE') {
                $existente->update(['status_envio' => 'ENVIADA', 'ack' => max($existente->ack, 1)]);
            }

            return;
        }

        $parsed = MessagePayloadParser::parse($data);

        $timestamp = isset($data['messageTimestamp'])
          ? Carbon::createFromTimestamp((int) $data['messageTimestamp'])
          : now();

        try {
            $mensagem = WhatsappMensagem::create([
                'empresa_id' => $conversa->empresa_id,
                'conversa_id' => $conversa->id,
                'message_id' => $messageId,
                'direcao' => 'OUT',
                'tipo' => $parsed['tipo'],
                'body' => $parsed['body'],
                'media_mime' => $parsed['mime'],
                'ack' => 1,
                'status_envio' => 'ENVIADA',
                'message_timestamp' => $timestamp,
            ]);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $conversa->update([
            'last_message_at' => $timestamp,
            'last_message_preview' => MessagePayloadParser::preview($parsed),
        ]);

        broadcast(new NovaMensagemWhatsapp($mensagem, $conversa->user_id));
        broadcast(new ConversaWhatsappAtualizada($conversa->fresh()));
    }
}
