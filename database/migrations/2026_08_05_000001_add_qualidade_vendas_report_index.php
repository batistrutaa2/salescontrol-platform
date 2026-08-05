<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->index(['empresa_id', 'created_at', 'user_id', 'tabulacao_id'], 'vendas_qualidade_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropIndex('vendas_qualidade_periodo_idx');
        });
    }
};
