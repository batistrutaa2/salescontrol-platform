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
    Schema::create('vendas', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('empresa_id')->default(0);
      $table->unsignedBigInteger('user_id')->default(0);
      $table->unsignedBigInteger('contato_id')->default(0);

      $table->string('nome_contrato', 90)->nullable();
      $table->string('cpf_cnpj', 90)->nullable();
      $table->string('email', 50)->nullable();
      $table->date('data_vigencia')->useCurrent();
      $table->string('telefone1', 50)->nullable();
      $table->string('telefone2', 50)->nullable();
      $table->string('operadora', 50)->nullable();
      $table->string('nome_plano', 50)->nullable();
      $table->decimal('valor_contrato', 10, 2)->nullable();
      $table->string('obs_contrato', 50)->nullable();
      $table->timestamps();

      $table->primary('id');

      $table->foreign('empresa_id')->references('id')->on('empresas')
        ->onUpdate('no action')->onDelete('no action');

      $table->foreign('user_id')->references('id')->on('users')
        ->onUpdate('no action')->onDelete('no action');

      $table->foreign('contato_id')->references('id')->on('contatos')
        ->onUpdate('no action')->onDelete('no action');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('vendas');
  }
};
