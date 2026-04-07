<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preditiva_tabulacoes_hard', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            // Tabulação normalizada em UPPERCASE — ex: "NÃO TEM INTERESSE"
            $table->string('tabulacao', 150);
            $table->timestamps();

            $table->unique(['empresa_id', 'tabulacao']);
            $table->index('empresa_id');

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preditiva_tabulacoes_hard');
    }
};
