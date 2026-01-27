<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vendas;
use App\Models\Recebivel;
use App\Jobs\GerarRecebiveisJob;

class GerarRecebiveisRetroativos extends Command
{
    protected $signature = 'recebiveis:retroativos
        {--empresa_id= : Filtrar por empresa}
        {--limite=500 : Tamanho do chunk}
        {--dry-run : Apenas listar vendas sem gerar nada}';

    protected $description = 'Gerar recebiveis para vendas implantadas que ainda nao possuem lancamentos';

    public function handle(): int
    {
        $empresaId = $this->option('empresa_id');
        $limite = (int) $this->option('limite');
        $dryRun = $this->option('dry-run');

        // Vendas implantadas que NAO possuem nenhum recebivel
        $query = Vendas::query()
            ->whereNotNull('data_implantacao')
            ->whereDoesntHave('recebiveis');

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Nenhuma venda pendente de recebiveis encontrada.');
            return self::SUCCESS;
        }

        $totalJaLancadas = Vendas::query()
            ->whereNotNull('data_implantacao')
            ->whereHas('recebiveis')
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->count();

        $this->info("Vendas implantadas sem recebiveis: {$total}");
        $this->info("Vendas implantadas ja lancadas (ignoradas): {$totalJaLancadas}");

        if ($dryRun) {
            $this->warn('Modo dry-run: nenhum recebivel sera gerado.');
            $this->table(
                ['ID', 'Empresa', 'Contrato', 'Operadora', 'Valor', 'Implantacao'],
                $query->orderBy('id')->limit(50)->get()->map(fn($v) => [
                    $v->id,
                    $v->empresa_id,
                    mb_substr($v->nome_contrato, 0, 30),
                    $v->operadora,
                    'R$ ' . number_format((float) $v->valor_contrato, 2, ',', '.'),
                    $v->data_implantacao?->format('d/m/Y'),
                ])
            );
            if ($total > 50) {
                $this->info("... e mais " . ($total - 50) . " vendas.");
            }
            return self::SUCCESS;
        }

        if (!$this->confirm("Deseja gerar recebiveis para {$total} vendas?")) {
            $this->info('Operacao cancelada.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $dispatched = 0;

        $query->orderBy('id')->chunk($limite, function ($vendas) use ($bar, &$dispatched) {
            foreach ($vendas as $venda) {
                dispatch(new GerarRecebiveisJob($venda->id));
                $dispatched++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Rotina finalizada. {$dispatched} jobs disparados.");

        return self::SUCCESS;
    }
}
