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
    Schema::create('contatos', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('empresa_id')->default(0);
      $table->unsignedBigInteger('user_import_id')->default(0);
      $table->string('nome_base', 255)->nullable();
      $table->enum('status', ['Y', 'N'])->default('Y');
      $table->string('nome_cliente', 255)->nullable();
      $table->string('data_nascimento', 255)->nullable();
      $table->string('cpf', 255)->nullable();
      $table->string('plano', 255)->nullable();
      $table->string('categoria', 255)->nullable();
      $table->string('entidade', 255)->nullable();
      $table->string('telefone1', 255)->nullable();
      $table->string('telefone2', 255)->nullable();
      $table->string('telefone3', 255)->nullable();
      $table->string('email', 255)->nullable();
      $table->string('idades', 255)->nullable();
      $table->string('valor_plano_atual', 255)->nullable();
      $table->timestamps();

      $table->primary('id');

      $table->index('empresa_id');
      $table->foreign('empresa_id')->references('id')->on('empresas')
        ->onUpdate('no action')->onDelete('no action');

      $table->index('user_import_id');
      $table->foreign('user_import_id')->references('id')->on('users')
        ->onUpdate('no action')->onDelete('no action');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('contatos');
  }
};
