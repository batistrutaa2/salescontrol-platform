<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_apolices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedInteger('versao')->default(1);
            $table->string('arquivo_path', 255);
            $table->date('data_emissao')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['contrato_id', 'versao']);

            $table->foreign('contrato_id')->references('id')->on('lk_beneficios_contratos')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_apolices');
    }
};
