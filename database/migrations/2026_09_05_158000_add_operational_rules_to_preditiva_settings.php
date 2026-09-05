<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            $table->unsignedSmallInteger('mascote_dias_sem_atividade')->default(5)->after('limite_envio_diario');
            $table->unsignedTinyInteger('mascote_limite_sugestoes')->default(10)->after('mascote_dias_sem_atividade');
            $table->unsignedSmallInteger('lock_expiracao_horas')->default(2)->after('mascote_limite_sugestoes');
        });
    }

    public function down(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            $table->dropColumn([
                'mascote_dias_sem_atividade',
                'mascote_limite_sugestoes',
                'lock_expiracao_horas',
            ]);
        });
    }
};
