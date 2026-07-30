<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timeline de cada solicitação do pós-venda: quem abriu, mudanças de etapa,
 * prazo, responsável e observações.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_venda_solicitacao_historico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitacao_id');
            $table->unsignedBigInteger('user_id');
            $table->string('campo_alterado', 40);
            $table->string('valor_anterior')->nullable();
            $table->string('valor_novo')->nullable();
            $table->string('observacao', 500)->nullable();
            $table->timestamps();

            $table->foreign('solicitacao_id')->references('id')->on('pos_venda_solicitacoes')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
            $table->index('solicitacao_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_venda_solicitacao_historico');
    }
};
