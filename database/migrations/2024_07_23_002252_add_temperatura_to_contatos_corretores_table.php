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
    Schema::table('contatos_corretores', function (Blueprint $table) {
      $table->enum('temperatura', ['MORNO', 'QUENTE', 'FRIO'])->after('tabulacao_id')->default('FRIO');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('contatos_corretores', function (Blueprint $table) {
      $table->dropColumn('temperatura');
    });
  }
};
