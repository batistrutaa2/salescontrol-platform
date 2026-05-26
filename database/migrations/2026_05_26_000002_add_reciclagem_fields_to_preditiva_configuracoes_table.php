<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            // Dias sem nenhum contato para um lead frio ser elegivel ao reenvio para a preditiva
            $table->unsignedInteger('dias_sem_contato_reenvio')->default(90)->after('limite_descartes_hard');
            // Liga/desliga a rotina diaria de reciclagem automatica
            $table->boolean('envio_automatico_ativo')->default(false)->after('dias_sem_contato_reenvio');
            // Teto de leads enviados por execucao da rotina (evita inundar a vitrine)
            $table->unsignedInteger('limite_envio_diario')->default(500)->after('envio_automatico_ativo');
        });
    }

    public function down(): void
    {
        Schema::table('preditiva_configuracoes', function (Blueprint $table) {
            $table->dropColumn([
                'dias_sem_contato_reenvio',
                'envio_automatico_ativo',
                'limite_envio_diario',
            ]);
        });
    }
};
