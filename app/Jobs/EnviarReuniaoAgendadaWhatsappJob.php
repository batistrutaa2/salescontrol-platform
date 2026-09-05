<?php

namespace App\Jobs;

use App\Jobs\Concerns\UsesTenantContext;
use App\Models\ComercialReunioes;
use App\Models\Empresa;
use App\Services\ReuniaoAgendadaFormatter;
use App\Services\WhatsappService;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarReuniaoAgendadaWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesTenantContext;

    public int $tries = 3;

    public array $backoff = [60, 180, 600];

    public function __construct(public int $reuniaoId) {}

    public function handle(WhatsappService $whatsapp, ReuniaoAgendadaFormatter $formatter): void
    {
        $referencia = ComercialReunioes::withoutGlobalScopes()->find($this->reuniaoId);
        if (! $referencia) {
            Log::warning('ReuniaoAgendada: reunião não encontrada', [
                'reuniao_id' => $this->reuniaoId,
            ]);

            return;
        }

        app(TenantContext::class)->run((int) $referencia->empresa_id, function () use ($whatsapp, $formatter) {
            $empresaId = (int) app(TenantContext::class)->id();
            $reuniao = ComercialReunioes::with([
                'user' => fn ($query) => $query->tenantActor($empresaId),
                'manager' => fn ($query) => $query->tenantMember($empresaId),
            ])->find($this->reuniaoId);

            if (! $reuniao) {
                return;
            }

            $empresa = Empresa::find($empresaId);
            if (! $empresa || empty($empresa->whatsapp_token)) {
                Log::warning('ReuniaoAgendada: empresa sem whatsapp_token', [
                    'reuniao_id' => $reuniao->id,
                    'empresa_id' => $empresaId,
                ]);

                return;
            }

            $manager = $reuniao->manager;
            $criador = $reuniao->user;
            if (! $manager || ! $criador || empty($manager->whatsapp)) {
                Log::warning('ReuniaoAgendada: gestor ou criador inválido para a empresa', [
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
        });
    }
}
