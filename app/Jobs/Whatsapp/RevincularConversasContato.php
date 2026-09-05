<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\ConversaWhatsappAtualizada;
use App\Jobs\Concerns\UsesWhatsappTenantContext;
use App\Models\Contatos;
use App\Models\WhatsappConversa;
use App\Services\Whatsapp\PhoneMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RevincularConversasContato implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesWhatsappTenantContext;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $contatoId,
        public int $userId
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        $contato = Contatos::find($this->contatoId);

        if (! $contato) {
            return;
        }

        $numerosNormalizados = collect([$contato->telefone1, $contato->telefone2, $contato->telefone3])
            ->map(fn ($telefone) => PhoneMatcher::normalizar($telefone))
            ->filter()
            ->unique()
            ->values();

        if ($numerosNormalizados->isEmpty()) {
            return;
        }

        $conversas = WhatsappConversa::where('user_id', $this->userId)
            ->where('empresa_id', $contato->empresa_id)
            ->whereNull('contato_id')
            ->whereIn('numero_normalizado', $numerosNormalizados)
            ->get();

        foreach ($conversas as $conversa) {
            $conversa->update(['contato_id' => $contato->id]);
            broadcast(new ConversaWhatsappAtualizada($conversa->fresh()));
        }
    }
}
