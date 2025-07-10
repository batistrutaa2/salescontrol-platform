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
        Schema::create('lead_atividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->default(0);
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('contato_id')->default(0);
            $table->unsignedBigInteger('tabulacao_anterior_id')->default(0);
            $table->unsignedBigInteger('tabulacao_atual_id')->default(0);
            $table->string('log_descricao')->nullable();

            $table->primary('id');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onUpdate('no action')->onDelete('no action');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign('contato_id')->references('id')->on('contatos')->onUpdate('no action')->onDelete('no action');
            $table->foreign('tabulacao_anterior_id')->references('id')->on('tabulacoes')->onUpdate('no action')->onDelete('no action');
            $table->foreign('tabulacao_atual_id')->references('id')->on('tabulacoes')->onUpdate('no action')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_atividades');
    }
};
