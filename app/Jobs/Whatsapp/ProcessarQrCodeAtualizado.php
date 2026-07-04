<?php

namespace App\Jobs\Whatsapp;

use App\Events\Whatsapp\QrCodeAtualizado;
use App\Models\WhatsappInstancia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessarQrCodeAtualizado implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $instancia->update(['status' => 'QRCODE', 'last_status_at' => now()]);

        broadcast(new QrCodeAtualizado($instancia->user_id));
    }
}
