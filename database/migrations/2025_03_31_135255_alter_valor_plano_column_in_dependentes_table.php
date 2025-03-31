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
        Schema::table('dependentes', function (Blueprint $table) {
          $table->string('valor_plano')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dependentes', function (Blueprint $table) {
          $table->decimal('valor_plano', 10, 2)->change();
        });
    }
};
