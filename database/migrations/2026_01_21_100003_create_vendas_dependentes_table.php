<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabela para armazenar dependentes de titulares em uma venda PME
     */
    public function up(): void
    {
        Schema::create('vendas_dependentes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venda_id');
            $table->unsignedBigInteger('titular_id'); // FK para vendas_titulares

            // Dados pessoais
            $table->string('nome', 100);
            $table->string('cpf', 14)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('email', 100)->nullable();
            $table->string('telefone1', 50)->nullable();
            $table->string('telefone2', 50)->nullable();

            // Parentesco com o titular
            $table->enum('parentesco', ['CONJUGE', 'FILHO', 'PAI_MAE', 'SOBRINHO', 'OUTROS'])->nullable();

            // Plano e coparticipação (se diferente do titular)
            $table->unsignedBigInteger('plano_id')->nullable();
            $table->enum('coparticipacao', ['Y', 'N', 'PARCIAL', 'COMPLETA'])->nullable();

            // Informações de portabilidade/plano anterior
            $table->enum('plano_anterior', ['SIM', 'NAO'])->default('NAO');
            $table->unsignedBigInteger('operadora_anterior_id')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('venda_id')
                ->references('id')
                ->on('vendas')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('titular_id')
                ->references('id')
                ->on('vendas_titulares')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('plano_id')
                ->references('id')
                ->on('planos')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('operadora_anterior_id')
                ->references('id')
                ->on('operadoras')
                ->onUpdate('no action')
                ->onDelete('no action');

            // Índices
            $table->index('venda_id');
            $table->index('titular_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendas_dependentes');
    }
};
