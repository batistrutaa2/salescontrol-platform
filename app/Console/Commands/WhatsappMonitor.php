<?php

namespace App\Console\Commands;

use App\Events\Whatsapp\StatusInstanciaAtualizado;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use App\Services\Evolution\EvolutionApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsappMonitor extends Command
{
    protected $signature = 'whatsapp:monitor';

    protected $description = 'Confere o estado real das instâncias WhatsApp na Evolution e sincroniza divergências';

    public function handle(WhatsappInstanciaRepositoryInterface $instanciaRepository, EvolutionApiService $evolution): int
    {
        foreach ($instanciaRepository->getConectadas() as $instancia) {
            try {
                $estado = $evolution->connectionState($instancia->instance_name);
                $state = data_get($estado, 'instance.state');
            } catch (\Throwable $e) {
                Log::warning('whatsapp:monitor — falha ao consultar instância', [
                    'instance' => $instancia->instance_name,
                    'erro' => $e->getMessage(),
                ]);

                continue;
            }

            $statusReal = match ($state) {
                'open' => 'CONECTADA',
                'connecting' => 'QRCODE',
                'close' => 'DESCONECTADA',
                default => null,
            };

            if ($statusReal !== null && $statusReal !== $instancia->status) {
                $instancia->update(['status' => $statusReal, 'last_status_at' => now()]);
                broadcast(new StatusInstanciaAtualizado($instancia->fresh()));

                $this->info("{$instancia->instance_name}: {$statusReal}");
            }
        }

        return self::SUCCESS;
    }
}
