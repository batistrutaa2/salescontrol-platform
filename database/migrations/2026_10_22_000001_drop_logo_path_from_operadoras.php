<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A logo da operadora só aparecia no Estudo comercial, removido em 22/10/2026.
 * Em produção nenhuma operadora tinha logo preenchida nessa data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('operadoras', 'logo_path')) {
            Schema::table('operadoras', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('operadoras', 'logo_path')) {
            Schema::table('operadoras', function (Blueprint $table) {
                $table->string('logo_path', 500)->nullable()->after('cor_marca');
            });
        }
    }
};
