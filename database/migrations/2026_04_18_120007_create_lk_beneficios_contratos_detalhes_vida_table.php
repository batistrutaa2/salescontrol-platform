<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_contratos_detalhes_vida', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id')->unique();
            $table->decimal('capital_segurado', 14, 2)->nullable();
            $table->decimal('cobertura_morte', 14, 2)->nullable();
            $table->decimal('cobertura_invalidez', 14, 2)->nullable();
            $table->decimal('cobertura_doencas_graves', 14, 2)->nullable();
            $table->boolean('possui_assistencia_funeral')->default(false);
            $table->json('beneficiarios_designados')->nullable();
            $table->timestamps();

            $table->foreign('contrato_id')->references('id')->on('lk_beneficios_contratos')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_contratos_detalhes_vida');
    }
};
