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
    Schema::table('comentarios', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id')->nullable()->change();
      $table->enum('legado', ['Y', 'N'])->default('N')->after('anotacao');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('comentarios', function (Blueprint $table) {
      $table->unsignedBigInteger('user_id')->nullable(false)->change();
      $table->dropColumn('legado');
    });
  }
};
