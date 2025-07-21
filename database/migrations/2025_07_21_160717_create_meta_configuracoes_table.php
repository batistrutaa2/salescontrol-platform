<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meta_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');

            $table->string('nome'); // Ex: "Meta Julho 2025"
            $table->enum('tipo_calculo', ['VALOR', 'QUANTIDADE'])->default('VALOR');
            $table->enum('periodo', ['MENSAL', 'SEMANAL', 'PERSONALIZADO'])->default('MENSAL');

            $table->date('data_inicio')->nullable(); // usado para "PERSONALIZADO"
            $table->date('data_fim')->nullable();    // idem

            $table->boolean('meta_individual')->default(true); // por vendedor ou geral
            $table->boolean('ativa')->default(true);

            $table->text('descricao')->nullable();
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_configuracoes');
    }
};
