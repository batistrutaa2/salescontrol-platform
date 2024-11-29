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
    Schema::create('ramais', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('empresa_id')->default(0);
      $table->unsignedBigInteger('user_id')->default(0);
      $table->string('ramal')->nullable();
      $table->timestamps();

      $table->primary('id');
      $table->foreign('empresa_id')->references('id')->on('empresas')->onUpdate('no action')->onDelete('no action');
      $table->foreign('user_id')->references('id')->on('users')->onUpdate('no action')->onDelete('no action');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ramais', function (Blueprint $table) {
      //
    });
  }
};
