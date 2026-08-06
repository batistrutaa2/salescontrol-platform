<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->date('data_retorno')->nullable()->after('data_limite');
            $table->index(['empresa_id', 'status', 'data_retorno']);
        });
    }

    public function down(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'status', 'data_retorno']);
            $table->dropColumn('data_retorno');
        });
    }
};
