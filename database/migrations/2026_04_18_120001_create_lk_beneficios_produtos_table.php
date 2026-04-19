<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_produtos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nome', 120);
            $table->enum('tipo', ['VIDA', 'ODONTO', 'PREVIDENCIA', 'PATRIMONIAL']);
            $table->string('subtipo', 80)->nullable();
            $table->unsignedBigInteger('operadora_id')->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'tipo']);
            $table->index('operadora_id');

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('operadora_id')->references('id')->on('operadoras')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_produtos');
    }
};
