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
          $table->string('telefone_1')->nullable()->after('parentesco');
          $table->string('telefone_2')->nullable()->after('telefone_1');
          $table->string('telefone_3')->nullable()->after('telefone_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dependentes', function (Blueprint $table) {
          $table->dropColumn(['telefone_1', 'telefone_2', 'telefone_3']);
        });
    }
};
