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
    Schema::table('contatos', function (Blueprint $table) {
      $table->decimal('valor_negociacao', 10, 2)->after('valor_plano_atual')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('contatos', function (Blueprint $table) {
      $table->dropColumn('valor_negociacao');
    });
  }
};
