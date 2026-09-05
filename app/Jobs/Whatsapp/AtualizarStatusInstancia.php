<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\StatusInstanciaAtualizado;
use App\Jobs\Concerns\UsesWhatsappTenantContext;
use App\Models\WhatsappInstancia;
use App\Services\Whatsapp\PhoneMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AtualizarStatusInstancia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesWhatsappTenantContext;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(
        public int $instanciaId,
        public array $payload
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(): void
    {
        $instancia = WhatsappInstancia::find($this->instanciaId);

        if (! $instancia) {
            return;
        }

        $data = $this->payload['data'] ?? [];
        $state = $data['state'] ?? null;

        $status = match ($state) {
            'open' => 'CONECTADA',
            'connecting' => 'QRCODE',
            'close' => 'DESCONECTADA',
            default => null,
        };

        if ($status === null) {
            return;
        }

        $atributos = [
            'status' => $status,
            'last_status_at' => now(),
        ];

        if ($status === 'CONECTADA') {
            $atributos['connected_at'] = now();

            if (! empty($data['wuid'])) {
                $atributos['numero_conectado'] = PhoneMatcher::numeroDoJid($data['wuid']);
            }
        }

        $instancia->update($atributos);

        broadcast(new StatusInstanciaAtualizado($instancia->fresh()));
    }
}
