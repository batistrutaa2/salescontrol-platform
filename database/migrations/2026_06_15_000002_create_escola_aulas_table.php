<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escola_aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('escola_modulo_id')->constrained('escola_modulos')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('video_path')->nullable();
            $table->string('video_nome_original')->nullable();
            $table->string('video_mime')->nullable();
            $table->unsignedBigInteger('video_tamanho_bytes')->nullable();
            $table->unsignedInteger('duracao_segundos')->nullable();
            $table->integer('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'escola_modulo_id', 'ordem']);
            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escola_aulas');
    }
};
