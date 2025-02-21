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
    Schema::create('empresas', function (Blueprint $table) {
      $table->id();
      $table->string('nome_fantasia', 255)->default('');
      $table->string('cpf_cnpj', 15)->default('');
      $table->string('telefone', 15)->default('');
      $table->string('email', 255)->default('');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('empresas');
  }
};
