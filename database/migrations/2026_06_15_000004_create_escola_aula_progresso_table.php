<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escola_aula_progresso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('escola_aula_id')->constrained('escola_aulas')->cascadeOnDelete();
            $table->unsignedInteger('ultima_posicao_segundos')->default(0);
            $table->unsignedTinyInteger('percentual')->default(0);
            $table->boolean('concluida')->default(false);
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'escola_aula_id'], 'uq_progresso_user_aula');
            $table->index(['empresa_id', 'escola_aula_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escola_aula_progresso');
    }
};
