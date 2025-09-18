<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('operadora_id');
            $table->string('nome');
            $table->enum('status', ['Y', 'N'])->default('Y');
            $table->enum('acomodacao', ['ENFERMARIA', 'APARTAMENTO'])->default('ENFERMARIA');

            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('operadora_id')->references('id')->on('operadoras');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
