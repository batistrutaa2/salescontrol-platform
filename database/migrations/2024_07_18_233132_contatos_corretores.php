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
    Schema::create('contatos_corretores', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('empresa_id')->default(0);
      $table->unsignedBigInteger('contato_id')->default(0);
      $table->unsignedBigInteger('user_id')->default(0);
      $table->unsignedBigInteger('tabulacao_id')->default(0);
      $table->timestamps();

      $table->index('empresa_id');
      $table->foreign('empresa_id')->references('id')->on('empresas')
        ->onUpdate('no action')->onDelete('no action');

      $table->index('user_id');
      $table->foreign('user_id')->references('id')->on('users')
        ->onUpdate('no action')->onDelete('no action');

      $table->index('contato_id');
      $table->foreign('contato_id')->references('id')->on('contatos')
        ->onUpdate('no action')->onDelete('no action');

      $table->index('tabulacao_id');
      $table->foreign('tabulacao_id')->references('id')->on('tabulacoes')
        ->onUpdate('no action')->onDelete('no action');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('contatos_corretores');
  }
};
