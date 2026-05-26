<?php

namespace App\Console\Commands;

use App\Models\PreditivaConfiguracao;
use App\Repositories\Eloquent\ContatosRepository;
use App\Services\ReciclagemLeadsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ReciclarLeadsFrios extends Command
{
    protected $signature = 'preditiva:reciclar-frios
                            {--dry-run : Apenas lista as quantidades, sem enviar}
                            {--empresa= : Limita a uma empresa especifica (ignora o flag de automatico)}';

    protected $description = 'Envia para a preditiva os leads frios (>N dias sem contato) elegiveis para reciclagem';

    public function handle(ReciclagemLeadsService $service, ContatosRepository $repo): int
    {
        $dryRun     = (bool) $this->option('dry-run');
        $empresaOpt = $this->option('empresa');

        $this->info($dryRun ? '🔍 Dry-run — nenhuma alteracao sera feita' : '⚙️  Execucao — leads serao enviados a preditiva');

        $configs = PreditivaConfiguracao::query()
            ->when($empresaOpt, fn($q) => $q->where('empresa_id', (int) $empresaOpt))
            ->when(!$empresaOpt, fn($q) => $q->where('envio_automatico_ativo', true))
            ->get();

        if ($configs->isEmpty()) {
            $this->warn('Nenhuma empresa elegivel (envio automatico desligado ou empresa inexistente).');
            return self::SUCCESS;
        }

        foreach ($configs as $config) {
            $empresaId = (int) $config->empresa_id;
            $dias      = (int) $config->dias_sem_contato_reenvio;
            $limite    = (int) $config->limite_envio_diario;

            $lock = Cache::lock("reciclar-frios:{$empresaId}", 600);
            if (!$lock->get()) {
                $this->warn("Empresa {$empresaId}: execucao ja em andamento, pulando.");
                continue;
            }

            try {
                $elegiveis = $repo->getResumoReciclagem($empresaId, $dias)['elegiveis_agora'];
                $this->info("Empresa {$empresaId} | dias={$dias} | limite={$limite} | elegiveis agora: {$elegiveis}");

                if ($dryRun) {
                    $this->line("  [dry-run] enviaria ate " . min($limite, $elegiveis) . " leads.");
                    continue;
                }

                $r = $service->enviarElegiveisEmLote($empresaId, null, 'AUTOMATICO', null, $limite);
                $this->info("  Enviados: {$r['enviados']} | Ignorados: {$r['ignorados']} | Erros: {$r['erros']}");
            } finally {
                $lock->release();
            }
        }

        return self::SUCCESS;
    }
}
