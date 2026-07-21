<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fluxo passo a passo da portabilidade: REUNINDO_DOCUMENTOS → ENVIADA_ANALISE →
 * CONCLUIDA | NEGADA (ver App\Enums\FasePortabilidade). As fases finais fecham o
 * processo (status = CONCLUIDA); NEGADA fica distinguível pela própria fase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas_portabilidades', function (Blueprint $table) {
            $table->string('fase', 30)->default('REUNINDO_DOCUMENTOS')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('vendas_portabilidades', function (Blueprint $table) {
            $table->dropColumn('fase');
        });
    }
};
