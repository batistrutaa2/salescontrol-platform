<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comissionamento_configuracao', function (Blueprint $table) {
            $table->decimal('percentual_angariacao', 5, 2)->default(0)->after('percentual');
        });

        DB::table('comissionamento_configuracao')
            ->whereRaw('UPPER(grade) = ?', ['JUNIOR'])
            ->update(['percentual_angariacao' => 10]);
        DB::table('comissionamento_configuracao')
            ->whereRaw('UPPER(grade) != ?', ['JUNIOR'])
            ->update(['percentual_angariacao' => 50]);

        Schema::table('comissionamento_configuracao', function (Blueprint $table) {
            $table->unique(['empresa_id', 'user_id'], 'cc_empresa_usuario_unique');
        });
    }

    public function down(): void
    {
        Schema::table('comissionamento_configuracao', function (Blueprint $table) {
            $table->dropUnique('cc_empresa_usuario_unique');
            $table->dropColumn('percentual_angariacao');
        });
    }
};
