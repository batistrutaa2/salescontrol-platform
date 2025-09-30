<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vendas;
use App\Jobs\GerarRecebiveisJob;

class GerarRecebiveisRetroativos extends Command
{
    /**
     * O nome do comando para rodar no terminal.
     *
     * @var string
     */
    protected $signature = 'recebiveis:retroativos {--empresa_id=} {--limite=500}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Gerar recebíveis retroativos para todas as vendas já implantadas';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        $empresaId = $this->option('empresa_id');
        $limite = (int) $this->option('limite');

        $query = Vendas::query()
            ->whereNotNull('data_implantacao');

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $total = $query->count();
        $this->info("🔎 Encontradas {$total} vendas implantadas...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunk($limite, function ($vendas) use ($bar) {
            foreach ($vendas as $venda) {
                dispatch(new GerarRecebiveisJob($venda->id));
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('✅ Rotina finalizada. Jobs de recebíveis retroativos foram disparados.');

        return self::SUCCESS;
    }
}
