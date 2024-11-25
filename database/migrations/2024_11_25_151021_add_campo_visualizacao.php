<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('comentarios', function (Blueprint $table) {
      $table->enum('visivel', ['Y', 'N'])->default("Y")->after('legado');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('comentarios', function (Blueprint $table) {
      $table->dropColumn('visivel');
    });
  }
};
