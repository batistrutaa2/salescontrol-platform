<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_beneficiarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');
            $table->enum('tipo', ['TITULAR', 'DEPENDENTE']);
            $table->string('nome', 150);
            $table->string('cpf', 20)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('grau_parentesco', 40)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->enum('status', ['ATIVO', 'EXCLUIDO'])->default('ATIVO');
            $table->date('data_inclusao')->nullable();
            $table->date('data_exclusao')->nullable();
            $table->timestamps();

            $table->index(['contrato_id', 'status']);
            $table->index('cpf');

            $table->foreign('contrato_id')->references('id')->on('lk_beneficios_contratos')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_beneficiarios');
    }
};
