<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('excluir_ranking')->default(false)->after('ativo');
        });

        // Marca os usuários que historicamente não devem entrar nos rankings
        // (regra de negócio fornecida pela administração). Comparação case-insensitive
        // para tolerar variações de cadastro entre tenants.
        $nomesExcluidos = [
            'LEANDRO ALVES FREITAS DOS SANTOS',
            'KAIQUE CELSO OLIVEIRA ALBERTIN',
            'MIKE ANDREW TEIXEIRA LICAS',
        ];

        foreach ($nomesExcluidos as $nome) {
            DB::table('users')
                ->whereRaw('UPPER(TRIM(name)) = ?', [mb_strtoupper(trim($nome), 'UTF-8')])
                ->update(['excluir_ranking' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('excluir_ranking');
        });
    }
};
