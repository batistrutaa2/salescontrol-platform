<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etapas configuráveis do fluxo de cada tipo de solicitação do pós-venda
 * (colunas do kanban da Central de Solicitações). Cada empresa define as
 * etapas de cada tipo; a natureza (EM_ANDAMENTO/CONCLUIDA/CANCELADA) dá a
 * semântica de encerramento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_venda_fluxo_etapas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('tipo', 40);
            $table->string('nome', 60);
            $table->string('cor', 20)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->string('natureza', 20)->default('EM_ANDAMENTO');
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->index(['empresa_id', 'tipo', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_venda_fluxo_etapas');
    }
};
