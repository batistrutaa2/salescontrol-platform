<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_parcelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedInteger('numero_parcela');
            $table->char('competencia', 7); // YYYY-MM
            $table->decimal('valor', 12, 2);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->enum('status', ['PENDENTE', 'PAGA', 'ATRASADA', 'CANCELADA'])->default('PENDENTE');
            $table->string('path_boleto', 255)->nullable();
            $table->timestamps();

            $table->unique(['contrato_id', 'numero_parcela']);
            $table->index(['contrato_id', 'status']);
            $table->index('competencia');

            $table->foreign('contrato_id')->references('id')->on('lk_beneficios_contratos')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_parcelas');
    }
};
