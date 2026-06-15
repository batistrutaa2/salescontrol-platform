<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escola_aula_materiais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('escola_aula_id')->constrained('escola_aulas')->cascadeOnDelete();
            $table->string('titulo')->nullable();
            $table->string('path_s3');
            $table->string('nome_original');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('tamanho_bytes')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'escola_aula_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escola_aula_materiais');
    }
};
