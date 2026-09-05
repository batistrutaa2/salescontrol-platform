<?php

namespace App\Console\Commands;

use App\Events\Whatsapp\StatusInstanciaAtualizado;
use App\Models\Empresa;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use App\Services\Evolution\EvolutionApiService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsappMonitor extends Command
{
    protected $signature = 'whatsapp:monitor {--empresa= : Limita a uma empresa específica}';

    protected $description = 'Confere o estado real das instâncias WhatsApp na Evolution e sincroniza divergências';

    public function handle(WhatsappInstanciaRepositoryInterface $instanciaRepository, EvolutionApiService $evolution): int
    {
        $empresas = $this->empresasSelecionadas();
        if ($empresas === null) {
            return self::FAILURE;
        }

        $context = app(TenantContext::class);
        $context->clear();

        try {
            foreach ($empresas as $empresaId) {
                $context->run($empresaId, function () use ($instanciaRepository, $evolution): void {
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
                });
            }
        } finally {
            $context->clear();
        }

        return self::SUCCESS;
    }

    private function empresasSelecionadas(): ?array
    {
        $empresaId = filter_var($this->option('empresa'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('empresa') !== null && $empresaId === false) {
            $this->error('Empresa inválida.');

            return null;
        }

        $ids = Empresa::query()
            ->when($empresaId, fn ($query) => $query->whereKey($empresaId))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($empresaId && $ids === []) {
            $this->error('Empresa inválida.');

            return null;
        }

        return $ids;
    }
}
