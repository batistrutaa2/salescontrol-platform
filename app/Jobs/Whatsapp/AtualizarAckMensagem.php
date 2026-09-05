<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\AckMensagemAtualizado;
use App\Jobs\Concerns\UsesWhatsappTenantContext;
use App\Models\WhatsappMensagem;
use App\Services\Whatsapp\MessagePayloadParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AtualizarAckMensagem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesWhatsappTenantContext;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $instanciaId,
        public array $payload
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        $data = $this->payload['data'] ?? [];
        $messageId = $data['keyId'] ?? data_get($data, 'key.id');
        $ack = MessagePayloadParser::ackFromStatus($data['status'] ?? null);

        if (! $messageId || $ack === null) {
            return;
        }

        $mensagem = WhatsappMensagem::where('message_id', $messageId)
            ->whereHas('conversa', fn ($q) => $q->where('instancia_id', $this->instanciaId))
            ->first();

        // Ack nunca regride (READ não volta para DELIVERY)
        if (! $mensagem || $mensagem->ack >= $ack) {
            return;
        }

        $mensagem->update(['ack' => $ack]);

        $conversa = $mensagem->conversa;

        broadcast(new AckMensagemAtualizado($conversa->user_id, $conversa->id, $messageId, $ack));
    }
}
