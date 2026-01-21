<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabela para armazenar beneficiários de portabilidade em uma venda PME
     */
    public function up(): void
    {
        Schema::create('vendas_portabilidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venda_id');

            // Dados do beneficiário
            $table->string('nome', 100);
            $table->string('cpf', 14)->nullable();
            $table->date('data_nascimento')->nullable();

            // Operadora anterior (de onde está vindo)
            $table->unsignedBigInteger('operadora_anterior_id')->nullable();
            $table->string('plano_anterior', 100)->nullable(); // Nome do plano anterior
            $table->string('numero_carteirinha', 50)->nullable();

            // Ordem/sequencial
            $table->integer('sequencial')->default(1);

            $table->timestamps();

            // Foreign keys
            $table->foreign('venda_id')
                ->references('id')
                ->on('vendas')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('operadora_anterior_id')
                ->references('id')
                ->on('operadoras')
                ->onUpdate('no action')
                ->onDelete('no action');

            // Índices
            $table->index('venda_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendas_portabilidades');
    }
};
