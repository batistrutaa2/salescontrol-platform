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
        Schema::create('preditiva_regras_priorizacao', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->string('campo', 50);
            $table->enum('operador', ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN']);
            $table->text('valor');
            $table->integer('peso')->default(10);
            $table->enum('ativo', ['Y', 'N'])->default('Y');
            $table->integer('ordem')->default(0);
            $table->timestamps();

            $table->foreign('empresa_id')
                ->references('id')
                ->on('empresas')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->index(['empresa_id', 'ativo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preditiva_regras_priorizacao');
    }
};
