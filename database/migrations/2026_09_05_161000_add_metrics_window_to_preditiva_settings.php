<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            $table->unsignedSmallInteger('indicadores_janela_dias')->default(30)->after('lock_expiracao_horas');
        });
    }

    public function down(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            $table->dropColumn('indicadores_janela_dias');
        });
    }
};
