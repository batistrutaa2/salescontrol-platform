<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_contratos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('produto_id');
            $table->unsignedBigInteger('venda_origem_id')->nullable();
            $table->unsignedBigInteger('pessoa_id')->nullable();
            $table->unsignedBigInteger('empresa_lemit_id')->nullable();

            $table->string('numero_apolice', 60)->nullable();
            $table->string('numero_proposta', 60)->nullable();

            $table->enum('cliente_tipo', ['PF', 'PJ']);
            $table->string('cpf_cnpj', 20);
            $table->string('nome_cliente', 150);

            $table->enum('status', ['EM_IMPLANTACAO', 'ATIVO', 'SUSPENSO', 'CANCELADO'])
                ->default('EM_IMPLANTACAO');

            $table->date('data_proposta')->nullable();
            $table->date('data_vigencia_inicio')->nullable();
            $table->date('data_vigencia_fim')->nullable();
            $table->date('data_aniversario_reajuste')->nullable();

            $table->decimal('valor_mensal', 12, 2)->default(0);
            $table->decimal('valor_anual', 12, 2)->default(0);
            $table->enum('forma_pagamento', ['MENSAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL'])->default('MENSAL');
            $table->unsignedTinyInteger('dia_vencimento')->nullable();

            $table->unsignedInteger('vidas_total')->default(1);
            $table->unsignedInteger('vidas_titulares')->default(1);
            $table->unsignedInteger('vidas_dependentes')->default(0);

            $table->decimal('comissao_percentual_primeira', 6, 2)->default(0);
            $table->decimal('comissao_percentual_recorrente', 6, 2)->default(0);

            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'status']);
            $table->index(['empresa_id', 'cpf_cnpj']);
            $table->index('produto_id');
            $table->index('venda_origem_id');
            $table->index('user_id');

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('produto_id')->references('id')->on('lk_beneficios_produtos')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('venda_origem_id')->references('id')->on('vendas')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_contratos');
    }
};
