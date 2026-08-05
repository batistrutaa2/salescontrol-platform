<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreditivaTabulacoesHardSeeder extends Seeder
{
    /**
     * Insere as tabulações HARD padrão para todas as empresas existentes.
     * São chamadas quando o cliente ATENDE a ligação e dá uma negativa definitiva.
     *
     * Rodar com: ./dev artisan db:seed --class=PreditivaTabulacoesHardSeeder
     */
    public function run(): void
    {
        $empresaIds = DB::table('empresas')->pluck('id');

        $tabulacoesHard = [
            'NAO TEM INTERESSE',
            'DOENCA PRE EXISTENTE',
            'TROCOU DE PLANO RECENTE',
        ];

        $now = now();

        foreach ($empresaIds as $empresaId) {
            foreach ($tabulacoesHard as $tabulacao) {
                DB::table('preditiva_tabulacoes_hard')->updateOrInsert(
                    ['empresa_id' => $empresaId, 'tabulacao' => $tabulacao],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        $this->command->info('Tabulacoes HARD inseridas para ' . count($empresaIds) . ' empresa(s).');
    }
}
