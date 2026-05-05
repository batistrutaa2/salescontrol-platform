<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();
        $empresas = DB::table('empresas')->pluck('id');

        foreach ($empresas as $empresaId) {
            $exists = DB::table('tabulacoes')
                ->where('empresa_id', $empresaId)
                ->whereRaw('UPPER(descricao) = ?', ['ESTORNO'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('tabulacoes')->insert([
                'empresa_id' => $empresaId,
                'descricao' => 'ESTORNO',
                'tipo_tabulacao' => 'A',
                'efetivo' => 'N',
                'ordem_kanban' => '99',
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Não remove a tabulação no rollback — pode haver vendas referenciando.
    }
};
