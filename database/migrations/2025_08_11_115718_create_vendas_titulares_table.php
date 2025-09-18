<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendas_titulares', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relacionamento com a apólice (venda)
            $table->unsignedBigInteger('venda_id');

            // Dados do titular
            $table->string('nome', 90);
            $table->string('email', 90)->nullable();
            $table->string('telefone', 50)->nullable();

            // Plano
            $table->unsignedBigInteger('plano_id')->nullable();

            // Coparticipação: Y/N ou PARCIAL/COMPLETA
            $table->enum('coparticipacao', ['Y', 'N', 'PARCIAL', 'COMPLETA'])->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('venda_id')
                ->references('id')
                ->on('vendas')
                ->onDelete('cascade');

            $table->foreign('plano_id')
                ->references('id')
                ->on('planos')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendas_titulares');
    }
};
