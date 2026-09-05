<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedTinyInteger('financeiro_mrr_janela_meses')->default(3)->after('demandas_concluidas_janela_dias');
            $table->unsignedTinyInteger('financeiro_historico_meses')->default(12)->after('financeiro_mrr_janela_meses');
            $table->unsignedTinyInteger('financeiro_previsao_meses')->default(6)->after('financeiro_historico_meses');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'financeiro_mrr_janela_meses',
                'financeiro_historico_meses',
                'financeiro_previsao_meses',
            ]);
        });
    }
};
