<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            $table->unsignedSmallInteger('kanban_inatividade_alerta_dias')->default(7)->after('indicadores_janela_dias');
            $table->unsignedSmallInteger('kanban_inatividade_urgente_dias')->default(14)->after('kanban_inatividade_alerta_dias');
            $table->unsignedSmallInteger('kanban_inatividade_critica_dias')->default(20)->after('kanban_inatividade_urgente_dias');
        });
    }

    public function down(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            $table->dropColumn([
                'kanban_inatividade_alerta_dias',
                'kanban_inatividade_urgente_dias',
                'kanban_inatividade_critica_dias',
            ]);
        });
    }
};
