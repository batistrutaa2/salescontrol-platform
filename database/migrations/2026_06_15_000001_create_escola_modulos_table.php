<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escola_modulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('slug')->nullable();
            $table->string('capa_path')->nullable();
            $table->integer('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
            $table->index(['empresa_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escola_modulos');
    }
};
