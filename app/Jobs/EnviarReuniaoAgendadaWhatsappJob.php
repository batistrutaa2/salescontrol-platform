<?php

namespace App\Jobs;

use App\Models\ComercialReunioes;
use App\Models\Empresa;
use App\Services\ReuniaoAgendadaFormatter;
use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarReuniaoAgendadaWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $reuniaoId) {}

    public function handle(WhatsappService $whatsapp, ReuniaoAgendadaFormatter $formatter): void
    {
        $reuniao = ComercialReunioes::with(['user', 'manager'])->find($this->reuniaoId);
        if (! $reuniao) {
            Log::warning('ReuniaoAgendada: reunião não encontrada', [
                'reuniao_id' => $this->reuniaoId,
            ]);

            return;
        }

        $empresa = Empresa::find($reuniao->empresa_id);
        if (! $empresa || empty($empresa->whatsapp_token)) {
            Log::warning('ReuniaoAgendada: empresa sem whatsapp_token', [
                'reuniao_id' => $reuniao->id,
                'empresa_id' => $reuniao->empresa_id,
            ]);

            return;
        }

        $manager = $reuniao->manager;
        if (! $manager || empty($manager->whatsapp)) {
            Log::warning('ReuniaoAgendada: gestor sem whatsapp cadastrado', [
                'reuniao_id' => $reuniao->id,
                'manager_id' => $reuniao->manager_id,
            ]);

            return;
        }

        $body = $formatter->format($reuniao);
        $resp = $whatsapp->send($empresa->whatsapp_token, $manager->whatsapp, $body);

        Log::info('ReuniaoAgendada: envio', [
            'reuniao_id' => $reuniao->id,
            'empresa_id' => $empresa->id,
            'manager_id' => $manager->id,
            'success' => $resp['success'] ?? false,
        ]);
    }
}
