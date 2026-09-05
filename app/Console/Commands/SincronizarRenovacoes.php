<?php

namespace App\Console\Commands;

use App\Services\RenovacaoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SincronizarRenovacoes extends Command
{
    protected $signature = 'renovacoes:sincronizar {--dry-run : Apenas calcula, sem persistir} {--empresa= : Limita a uma empresa}';

    protected $description = 'Sincroniza a carteira de relacionamento e renovação';

    public function handle(RenovacaoService $service): int
    {
        $empresaId = $this->option('empresa') ? (int) $this->option('empresa') : null;
        if ($empresaId !== null && ! DB::table('empresas')->where('id', $empresaId)->exists()) {
            $this->error('Empresa inválida.');

            return self::FAILURE;
        }

        $r = $service->sincronizar((bool) $this->option('dry-run'), $empresaId);
        $this->table(['Normalizadas', 'Elegíveis', 'Criadas', 'Atualizadas', 'Suspensas'], [array_values($r)]);

        return self::SUCCESS;
    }
}
