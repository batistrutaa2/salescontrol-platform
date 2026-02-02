<?php

namespace App\Console\Commands;

use App\Services\RecebiveisImportacaoSysService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GerarRecebiveisImportacaoSys extends Command
{
    protected $signature = 'recebiveis:importacao-sys
                            {--data-corte=2026-01-01 : Data limite para marcar como pago (formato: YYYY-MM-DD)}
                            {--dry-run : Simula a execução sem salvar no banco}';

    protected $description = 'Gera recebíveis para vendas importadas do SYS e marca como pagos até a data de corte';

    public function handle(): int
    {
        $dataCorteString = $this->option('data-corte');
        $dryRun = $this->option('dry-run');

        try {
            $dataCorte = Carbon::parse($dataCorteString);
        } catch (\Exception $e) {
            $this->error("Data de corte inválida: {$dataCorteString}");

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('=== Geração de Recebíveis - Importação SYS ===');
        $this->info("Data de corte: {$dataCorte->format('d/m/Y')}");

        if ($dryRun) {
            $this->warn('MODO SIMULAÇÃO: Nenhum dado será salvo no banco.');
        }

        $this->newLine();
        $this->info('Processando vendas...');
        $this->newLine();

        $service = new RecebiveisImportacaoSysService($dataCorte, $dryRun);

        $totalVendas = \App\Models\Vendas::where('layout_venda', 'IMPORTACAO_SYS')
            ->whereDoesntHave('recebiveis')
            ->count();

        if ($totalVendas === 0) {
            $this->warn('Nenhuma venda com layout_venda = IMPORTACAO_SYS encontrada sem recebíveis.');

            return Command::SUCCESS;
        }

        $this->info("Encontradas {$totalVendas} vendas para processar.");
        $this->newLine();

        $service->executar();

        $resumo = $service->getResumo();

        $this->newLine();
        $this->info('=== RELATÓRIO FINAL ===');
        $this->newLine();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Vendas processadas', $resumo['vendas_processadas']],
                ['Vendas sem regra (ignoradas)', $resumo['vendas_sem_regra']],
                ['Vendas sem data implantação', $resumo['vendas_sem_data']],
                ['', ''],
                ['Recebíveis gerados (total)', $resumo['recebiveis_total']],
                ['  - Parcelas normais', $resumo['recebiveis_normais']],
                ['  - Parcelas vitalícias', $resumo['recebiveis_vitalicios']],
                ['', ''],
                ['Recebíveis PAGO', $resumo['recebiveis_pagos']],
                ['Recebíveis PENDENTE', $resumo['recebiveis_pendentes']],
            ]
        );

        if (! empty($resumo['warnings'])) {
            $this->newLine();
            $this->warn('Warnings:');
            foreach (array_slice($resumo['warnings'], 0, 20) as $warning) {
                $this->line("  - {$warning}");
            }
            if (count($resumo['warnings']) > 20) {
                $remaining = count($resumo['warnings']) - 20;
                $this->line("  ... e mais {$remaining} warning(s)");
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn('MODO SIMULAÇÃO: Execute novamente sem --dry-run para salvar os dados.');
        } else {
            $this->info('Processamento concluído com sucesso!');
        }

        return Command::SUCCESS;
    }
}
