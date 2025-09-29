<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_comissionamento', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('operadora_id')->constrained('operadoras')->cascadeOnDelete();

            $table->enum('categoria', ['PME', 'ADESAO'])->default('PME');
            $table->decimal('total_percentual', 6, 2)->nullable();
            $table->string('descricao')->nullable(); 
            $table->boolean('vitalicio')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_comissionamento');
    }
};
