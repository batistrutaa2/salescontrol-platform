<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up()
  {
    Schema::create('ranking_de_vendas', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('empresa_id')->default(0);
      $table->unsignedBigInteger('user_id')->default(0);

      $table->string('_id')->unique();
      $table->string('name');
      $table->bigInteger('cpf')->default(0);
      $table->string('company_id');
      $table->json('teams');
      $table->string('email')->nullable();

      $table->primary('id');
      $table->foreign('empresa_id')->references('id')->on('empresas')->onUpdate('no action')->onDelete('no action');
      $table->foreign('user_id')->references('id')->on('users')->onUpdate('no action')->onDelete('no action');

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::dropIfExists('ranking_de_vendas');
  }
};
