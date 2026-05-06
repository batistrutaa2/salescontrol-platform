<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\User;
use App\Services\ResumoOperacionalFormatter;
use App\Services\ResumoOperacionalService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarResumoDiarioWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 180, 600];

    public function __construct(
        public int $empresaId,
        public ?int $userId,
        public string $data,
        public ?string $phoneOverride = null,
    ) {}

    public static function dispatchToPhone(int $empresaId, string $phone, string $data)
    {
        return self::dispatch($empresaId, null, $data, $phone);
    }

    public function handle(
        WhatsappService $whatsapp,
        ResumoOperacionalService $service,
        ResumoOperacionalFormatter $formatter,
    ): void {
        $empresa = Empresa::find($this->empresaId);
        if (! $empresa || empty($empresa->whatsapp_token)) {
            Log::warning('ResumoDiario: empresa sem whatsapp_token', [
                'empresa_id' => $this->empresaId,
            ]);
            return;
        }

        if ($this->phoneOverride) {
            $numero       = $this->phoneOverride;
            $nomeAdmin    = 'Teste manual';
            $contextoLog  = "phone-test:{$numero}";
        } else {
            $admin = User::find($this->userId);
            if (! $admin || empty($admin->whatsapp)) {
                Log::warning('ResumoDiario: admin sem whatsapp', [
                    'user_id'    => $this->userId,
                    'empresa_id' => $this->empresaId,
                ]);
                return;
            }
            $numero      = $admin->whatsapp;
            $nomeAdmin   = $admin->name;
            $contextoLog = "user:{$admin->id}";
        }

        $data     = Carbon::parse($this->data, 'America/Sao_Paulo');
        $snapshot = $service->montarSnapshot($this->empresaId, $data);
        $body     = $formatter->format(
            $snapshot,
            $nomeAdmin,
            $empresa->nome_fantasia ?? 'Sua corretora',
            $data,
        );

        $resp = $whatsapp->send($empresa->whatsapp_token, $numero, $body);

        Log::info('ResumoDiario: envio', [
            'empresa_id'   => $this->empresaId,
            'destinatario' => $contextoLog,
            'data'         => $this->data,
            'success'      => $resp['success'] ?? false,
        ]);
    }
}
