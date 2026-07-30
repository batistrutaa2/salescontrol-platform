<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central de Solicitações do Pós-Venda: toda solicitação rotineira do setor
 * (cancelamento, portabilidade, boleto, reembolso...) vinculada a um contrato,
 * com prazo manual e etapa dentro do fluxo configurável do seu tipo.
 * `status` é denormalizado da natureza da etapa para KPIs baratos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venda_id');
            $table->unsignedBigInteger('empresa_id');
            $table->string('tipo', 40);
            $table->unsignedBigInteger('etapa_id');
            $table->string('titulo')->nullable();
            $table->string('descricao', 2000)->nullable();
            $table->string('status', 20)->default('ABERTA');
            $table->date('data_limite')->nullable();
            $table->string('origem', 20)->default('BACKOFFICE');
            $table->unsignedBigInteger('responsavel_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->dateTime('concluida_em')->nullable();
            $table->timestamps();

            $table->foreign('venda_id')->references('id')->on('vendas')->cascadeOnDelete();
            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('etapa_id')->references('id')->on('pos_venda_fluxo_etapas')
                ->onUpdate('no action')->onDelete('restrict');
            $table->foreign('responsavel_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');

            $table->index(['empresa_id', 'status', 'data_limite']);
            $table->index(['empresa_id', 'tipo']);
            $table->index(['empresa_id', 'etapa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_venda_solicitacoes');
    }
};
