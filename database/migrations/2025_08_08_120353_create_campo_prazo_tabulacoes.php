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
        Schema::table('tabulacoes', function (Blueprint $table) {
            $table->string('prazo')->nullable()->after('sub_tabulacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabulacoes', function (Blueprint $table) {
            $table->dropColumn('prazo');
        });
    }
};
