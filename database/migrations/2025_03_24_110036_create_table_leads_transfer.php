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
        Schema::create('transferencia_contatos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('contato_id');
            $table->unsignedBigInteger('de_users_id');
            $table->unsignedBigInteger('para_user_id');
            $table->unsignedBigInteger('responsavel_transferencia');

            $table->timestamps();
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('contato_id')->references('id')->on('contatos');
            $table->foreign('de_users_id')->references('id')->on('users');
            $table->foreign('para_user_id')->references('id')->on('users');
            $table->foreign('responsavel_transferencia')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencia_contatos');
    }
};
