<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciais_acesso', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('operadora_id')->nullable();
            $table->string('tipo', 50)->nullable();
            $table->string('nome', 255);
            $table->string('login', 255)->nullable();
            $table->string('senha', 255)->nullable();
            $table->text('observacao')->nullable();
            $table->enum('status', ['Y', 'N'])->default('Y');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('empresa_id');
            $table->index('operadora_id');

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('operadora_id')->references('id')->on('operadoras')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('updated_by')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciais_acesso');
    }
};
