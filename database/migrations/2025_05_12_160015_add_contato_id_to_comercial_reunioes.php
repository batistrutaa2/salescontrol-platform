<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('comercial_reunioes', 'contato_id')) {
            Schema::table('comercial_reunioes', function (Blueprint $table) {
                $table->unsignedBigInteger('contato_id')->nullable()->after('manager_id');
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM comercial_reunioes'))
            ->pluck('Key_name')
            ->unique();

        if (! $indexes->contains('comercial_reunioes_contato_id_index')) {
            Schema::table('comercial_reunioes', function (Blueprint $table) {
                $table->index('contato_id');
            });
        }

        $foreignExists = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'comercial_reunioes'
               AND CONSTRAINT_NAME = 'comercial_reunioes_contato_id_foreign'"
        );

        if (empty($foreignExists)) {
            Schema::table('comercial_reunioes', function (Blueprint $table) {
                $table->foreign('contato_id')->references('id')->on('contatos')
                    ->onUpdate('no action')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comercial_reunioes', function (Blueprint $table) {
            $table->dropForeign(['contato_id']);
            $table->dropIndex(['contato_id']);
            $table->dropColumn('contato_id');
        });
    }
};
