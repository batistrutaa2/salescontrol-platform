<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('vendas', function (Blueprint $table) {
      // Altera o campo obs_contrato para TEXT
      $table->text('obs_contrato')->change();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('vendas', function (Blueprint $table) {
      // Reverte a alteração (se necessário), por exemplo, para STRING com 255 caracteres
      $table->string('obs_contrato', 255)->change();
    });
  }
};
