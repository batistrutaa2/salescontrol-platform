<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedSmallInteger('pos_venda_aniversarios_janela_dias')
                ->default(30)
                ->after('financeiro_previsao_meses');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('pos_venda_aniversarios_janela_dias');
        });
    }
};
