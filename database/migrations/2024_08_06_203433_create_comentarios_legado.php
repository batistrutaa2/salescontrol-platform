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
    Schema::create('comentarios_legado', function (Blueprint $table) {
      $table->id();
      $table->string('nome_autor')->nullable();
      $table->string('nome_cliente')->nullable();
      $table->string('cpf')->nullable();
      $table->string('telefone1')->nullable();
      $table->string('telefone2')->nullable();
      $table->string('telefone3')->nullable();
      $table->text('anotacao')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('comentarios_legado');
  }
};
