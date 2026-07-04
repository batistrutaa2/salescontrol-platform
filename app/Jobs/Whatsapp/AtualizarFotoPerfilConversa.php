<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\ConversaWhatsappAtualizada;
use App\Models\WhatsappConversa;
use App\Services\Evolution\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AtualizarFotoPerfilConversa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [15, 60];

    public function __construct(public int $conversaId)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(EvolutionApiService $evolution): void
    {
        $conversa = WhatsappConversa::with('instancia')->find($this->conversaId);

        if (! $conversa || ! $conversa->instancia || $conversa->instancia->status !== 'CONECTADA') {
            return;
        }

        try {
            $fotoUrl = $evolution->fetchProfilePicture($conversa->instancia->instance_name, $conversa->numero);
        } catch (\Throwable) {
            // Contato sem foto ou com privacidade restrita — mantém a inicial
            return;
        }

        if ($fotoUrl && $fotoUrl !== $conversa->foto_url) {
            $conversa->update(['foto_url' => $fotoUrl]);
            broadcast(new ConversaWhatsappAtualizada($conversa->fresh()));
        }
    }
}
