<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prioridade manual na Central de Solicitações: o backoffice sinaliza o que
 * precisa furar a fila — prioritárias sobem para o topo entre as abertas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->boolean('prioridade')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->dropColumn('prioridade');
        });
    }
};
