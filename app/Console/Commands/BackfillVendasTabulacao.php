<?php

namespace App\Console\Commands;

use App\Enums\Tabulations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVendasTabulacao extends Command
{
    protected $signature = 'vendas:backfill-tabulacao {--dry-run : Apenas mostra o que seria feito, sem gravar}';

    protected $description = 'Preenche vendas.tabulacao_id a partir das fontes disponíveis (idempotente — só preenche NULLs)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $totalNull = DB::table('vendas')->whereNull('tabulacao_id')->count();
        $this->info("Vendas sem tabulacao_id: {$totalNull}");

        if ($totalNull === 0) {
            $this->info('Nada a fazer.');

            return self::SUCCESS;
        }

        // Etapa 1 — vendas implantadas: data_implantacao é o sinal mais forte
        // (imune ao bug de status compartilhado por contato).
        $etapa1 = DB::table('vendas')
            ->whereNull('tabulacao_id')
            ->whereNotNull('data_implantacao')
            ->count();
        $this->line("Etapa 1 (data_implantacao → IMPLANTADO): {$etapa1} vendas");

        if (! $dryRun && $etapa1 > 0) {
            DB::statement('
                UPDATE vendas
                SET tabulacao_id = ?, tabulacao_updated_at = updated_at
                WHERE tabulacao_id IS NULL AND data_implantacao IS NOT NULL
            ', [Tabulations::IMPLANTADO]);
        }

        // Etapa 2 — contatos com UMA venda só: o status em contatos_corretores é
        // confiável (apenas essa venda pode tê-lo movido). Só copia tabulações
        // administrativas (tipo A).
        $etapa2Where = "
            v.tabulacao_id IS NULL
            AND EXISTS (
                SELECT 1 FROM contatos_corretores cc
                JOIN tabulacoes t ON t.id = cc.tabulacao_id AND t.tipo_tabulacao = 'A'
                WHERE cc.contato_id = v.contato_id
            )
            AND (SELECT COUNT(*) FROM vendas v2 WHERE v2.contato_id = v.contato_id) = 1
        ";
        $etapa2 = DB::selectOne("SELECT COUNT(*) as total FROM vendas v WHERE {$etapa2Where}")->total;
        $this->line("Etapa 2 (contato com 1 venda → copia status do contato): {$etapa2} vendas");

        if (! $dryRun && $etapa2 > 0) {
            // Tabela derivada (materializada) evita o erro 1093 do MySQL ao
            // referenciar a própria tabela alvo do UPDATE.
            DB::statement("
                UPDATE vendas v
                JOIN (
                    SELECT contato_id FROM vendas GROUP BY contato_id HAVING COUNT(*) = 1
                ) unica ON unica.contato_id = v.contato_id
                JOIN contatos_corretores cc ON cc.contato_id = v.contato_id
                JOIN tabulacoes t ON t.id = cc.tabulacao_id AND t.tipo_tabulacao = 'A'
                SET v.tabulacao_id = cc.tabulacao_id, v.tabulacao_updated_at = cc.updated_at
                WHERE v.tabulacao_id IS NULL
            ");
        }

        // Etapa 3 — contatos multi-venda: último vendas_historico da venda
        // (melhor palpite restante; o histórico antigo pode estar atribuído à
        // venda errada por causa do bug, mas é o que existe).
        $etapa3 = DB::selectOne('
            SELECT COUNT(*) as total
            FROM vendas v
            JOIN (
                SELECT vh.venda_id, vh.tabulacao_nova_id
                FROM vendas_historico vh
                JOIN (SELECT venda_id, MAX(id) AS max_id FROM vendas_historico GROUP BY venda_id) ult
                  ON ult.max_id = vh.id
            ) h ON h.venda_id = v.id
            WHERE v.tabulacao_id IS NULL
        ')->total;
        $this->line("Etapa 3 (último histórico da venda): {$etapa3} vendas");

        if (! $dryRun && $etapa3 > 0) {
            DB::statement('
                UPDATE vendas v
                JOIN (
                    SELECT vh.venda_id, vh.tabulacao_nova_id, vh.created_at
                    FROM vendas_historico vh
                    JOIN (SELECT venda_id, MAX(id) AS max_id FROM vendas_historico GROUP BY venda_id) ult
                      ON ult.max_id = vh.id
                ) h ON h.venda_id = v.id
                SET v.tabulacao_id = h.tabulacao_nova_id, v.tabulacao_updated_at = h.created_at
                WHERE v.tabulacao_id IS NULL
            ');
        }

        // Etapa 4 — restante entra no início do funil do backoffice (VENDA).
        $etapa4 = DB::table('vendas')->whereNull('tabulacao_id')->count();
        if ($dryRun) {
            // No dry-run as etapas anteriores não gravaram; estima o residual.
            $etapa4 = max(0, $totalNull - $etapa1 - $etapa2 - $etapa3);
        }
        $this->line("Etapa 4 (restante → VENDA): {$etapa4} vendas");

        if (! $dryRun && $etapa4 > 0) {
            DB::statement('
                UPDATE vendas
                SET tabulacao_id = ?, tabulacao_updated_at = created_at
                WHERE tabulacao_id IS NULL
            ', [Tabulations::VENDA]);
        }

        if ($dryRun) {
            $this->warn('Dry-run: nenhuma alteração gravada.');
        } else {
            $restante = DB::table('vendas')->whereNull('tabulacao_id')->count();
            $this->info("Concluído. Vendas ainda sem tabulacao_id: {$restante}");
        }

        return self::SUCCESS;
    }
}
