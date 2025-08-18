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
        Schema::create('demandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('prioridade', ['BAIXA', 'MEDIA', 'ALTA'])->default('MEDIA');
            $table->enum('status', ['ABERTA', 'EM_ANDAMENTO', 'CONCLUIDA', 'CANCELADA'])->default('ABERTA');
            $table->date('data_limite')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandas');
    }
};
