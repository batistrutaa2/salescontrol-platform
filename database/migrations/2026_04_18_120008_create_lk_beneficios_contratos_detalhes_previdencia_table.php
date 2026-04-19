<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_contratos_detalhes_previdencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id')->unique();
            $table->enum('modalidade', ['PGBL', 'VGBL']);
            $table->enum('tabela_imposto', ['REGRESSIVA', 'PROGRESSIVA']);
            $table->decimal('aporte_mensal', 12, 2)->default(0);
            $table->decimal('aporte_inicial', 12, 2)->default(0);
            $table->decimal('rentabilidade_acumulada', 8, 4)->default(0);
            $table->date('data_ultimo_aporte')->nullable();
            $table->timestamps();

            $table->foreign('contrato_id')->references('id')->on('lk_beneficios_contratos')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_contratos_detalhes_previdencia');
    }
};
