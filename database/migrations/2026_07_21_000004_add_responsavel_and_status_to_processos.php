<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Painel operacional de processos (visão do gestor): cada processo passa a ter
 * um responsável (para reatribuir se alguém sai) e a portabilidade ganha um
 * ciclo aberto/concluído — sem isso não dá para contar "quantas em aberto".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venda_demandas', function (Blueprint $table) {
            $table->foreignId('responsavel_id')->nullable()->after('concluida_por')
                ->constrained('users')->nullOnDelete();
            $table->index('responsavel_id');
        });

        Schema::table('vendas_portabilidades', function (Blueprint $table) {
            $table->enum('status', ['PENDENTE', 'CONCLUIDA'])->default('PENDENTE')->after('sequencial');
            $table->foreignId('responsavel_id')->nullable()->after('status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('concluida_em')->nullable()->after('responsavel_id');
            $table->foreignId('concluida_por')->nullable()->after('concluida_em')
                ->constrained('users')->nullOnDelete();
            $table->index(['status', 'responsavel_id']);
        });
    }

    public function down(): void
    {
        Schema::table('venda_demandas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsavel_id');
        });
        Schema::table('vendas_portabilidades', function (Blueprint $table) {
            $table->dropIndex(['status', 'responsavel_id']);
            $table->dropConstrainedForeignId('responsavel_id');
            $table->dropConstrainedForeignId('concluida_por');
            $table->dropColumn('status');
        });
    }
};
