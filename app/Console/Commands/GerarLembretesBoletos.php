<?php

namespace App\Console\Commands;

use App\Services\BoletoLembreteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GerarLembretesBoletos extends Command
{
    protected $signature = 'boletos:lembrar {--empresa= : Limita a geração a uma empresa}';

    protected $description = 'Gera lembretes mensais de boletos para contratos implantados';

    public function handle(BoletoLembreteService $service): int
    {
        $empresa = $this->option('empresa');
        if ($empresa !== null && (! ctype_digit((string) $empresa) || ! DB::table('empresas')->where('id', $empresa)->exists())) {
            $this->error('Empresa inválida.');

            return self::FAILURE;
        }
        $total = $service->sincronizar($empresa === null ? null : (int) $empresa);
        $this->info("{$total} lembrete(s) gerado(s).");

        return self::SUCCESS;
    }
}
