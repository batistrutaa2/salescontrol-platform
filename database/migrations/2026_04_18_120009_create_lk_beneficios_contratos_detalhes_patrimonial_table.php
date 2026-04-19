<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_contratos_detalhes_patrimonial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id')->unique();
            $table->enum('tipo_bem', ['AUTO', 'RESIDENCIAL', 'EMPRESARIAL']);
            $table->string('descricao_bem', 255)->nullable();
            $table->decimal('valor_segurado', 14, 2)->default(0);
            $table->decimal('franquia', 14, 2)->default(0);
            $table->string('identificador_bem', 100)->nullable();
            $table->json('coberturas')->nullable();
            $table->timestamps();

            $table->foreign('contrato_id')->references('id')->on('lk_beneficios_contratos')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_contratos_detalhes_patrimonial');
    }
};
