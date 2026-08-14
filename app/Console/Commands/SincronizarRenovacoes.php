<?php

namespace App\Console\Commands;

use App\Services\RenovacaoService;
use Illuminate\Console\Command;

class SincronizarRenovacoes extends Command
{
    protected $signature = 'renovacoes:sincronizar {--dry-run : Apenas calcula, sem persistir} {--empresa= : Limita a uma empresa}';
    protected $description = 'Sincroniza a carteira de relacionamento e renovação';
    public function handle(RenovacaoService $service): int
    {
        $r = $service->sincronizar((bool) $this->option('dry-run'), $this->option('empresa') ? (int) $this->option('empresa') : null);
        $this->table(['Normalizadas', 'Elegíveis', 'Criadas', 'Atualizadas', 'Suspensas'], [array_values($r)]);
        return self::SUCCESS;
    }
}
