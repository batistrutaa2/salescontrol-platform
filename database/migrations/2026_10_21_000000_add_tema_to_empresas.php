<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tema visual por empresa. Nulo = visual padrão do SalesControl; 'licas' liga
 * o tema da LICAS CONSULTORIA (classe theme-licas no <html>).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('tema', 30)->nullable()->after('nome_fantasia');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('tema');
        });
    }
};
