<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimparPreditivaComVenda extends Command
{
    protected $signature = 'preditiva:limpar-com-venda
                            {empresa_id : ID da empresa}
                            {--confirmar : Executa a remoção. Sem esta flag roda em modo dry-run}';

    protected $description = 'Remove da preditiva leads que já possuem venda cadastrada (foram inseridos por engano)';

    public function handle(): int
    {
        $empresaId = (int) $this->argument('empresa_id');
        $confirmar = $this->option('confirmar');

        if ($empresaId < 1 || ! Empresa::query()->whereKey($empresaId)->exists()) {
            $this->error('Empresa inválida.');

            return self::FAILURE;
        }

        $this->info("Empresa ID: $empresaId");
        $this->info($confirmar ? '⚠️  MODO EXECUÇÃO — registros serão removidos' : '🔍 Dry-run — nenhuma alteração será feita');
        $this->newLine();

        $leads = DB::table('preditiva as p')
            ->join('contatos as c', function ($join) {
                $join->on('c.id', '=', 'p.contato_id')
                    ->on('c.empresa_id', '=', 'p.empresa_id');
            })
            ->join('vendas as v', function ($join) {
                $join->on('v.contato_id', '=', 'p.contato_id')
                    ->on('v.empresa_id', '=', 'p.empresa_id');
            })
            ->where('p.status', 'Y')
            ->where('p.empresa_id', $empresaId)
            ->select(
                'p.id as preditiva_id',
                'c.id as contato_id',
                'c.nome_cliente',
                'c.cpf',
                DB::raw('GROUP_CONCAT(DISTINCT v.id ORDER BY v.id DESC SEPARATOR ", ") as venda_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT v.numero_proposta ORDER BY v.id DESC SEPARATOR ", ") as propostas')
            )
            ->groupBy('p.id', 'c.id', 'c.nome_cliente', 'c.cpf')
            ->get();

        if ($leads->isEmpty()) {
            $this->info('✅ Nenhum lead na preditiva com venda cadastrada. Nada a fazer.');

            return self::SUCCESS;
        }

        $this->warn("Encontrados {$leads->count()} leads na preditiva que já possuem venda:");
        $this->newLine();

        $this->table(
            ['preditiva_id', 'contato_id', 'nome_cliente', 'cpf', 'venda_ids', 'propostas'],
            $leads->map(fn ($r) => [
                $r->preditiva_id,
                $r->contato_id,
                $r->nome_cliente,
                $r->cpf,
                $r->venda_ids,
                $r->propostas,
            ])->toArray()
        );
        $this->newLine();

        if (! $confirmar) {
            $this->warn('Rode com --confirmar para efetivar a remoção.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Confirma a remoção de {$leads->count()} registros da preditiva?")) {
            $this->info('Cancelado.');

            return self::SUCCESS;
        }

        $preditivaIds = $leads->pluck('preditiva_id')->toArray();

        DB::table('preditiva')
            ->where('empresa_id', $empresaId)
            ->whereIn('id', $preditivaIds)
            ->delete();

        $this->info("✅ {$leads->count()} registros removidos da preditiva com sucesso.");

        return self::SUCCESS;
    }
}
