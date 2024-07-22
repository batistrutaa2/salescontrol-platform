<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tabulacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->default(0);
            $table->string('descricao', 255)->nullable();
            $table->enum('tipo_tabulacao', ['C', 'A'])->default('C');
            $table->enum('efetivo', ['Y', 'N'])->default('Y');
            $table->string('ordem_kanban', 50)->nullable();
            $table->enum('status', ['Y', 'N'])->default('Y');
            $table->timestamps();


            $table->index('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresas')
              ->onUpdate('no action')->onDelete('no action');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabulacoes');
    }
};
