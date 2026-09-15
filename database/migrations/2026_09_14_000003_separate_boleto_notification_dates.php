<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boleto_agendas', function (Blueprint $table) {
            $table->date('proxima_notificacao')->nullable()->index();
            $table->unsignedTinyInteger('dia_notificacao')->nullable();
            $table->softDeletes();
        });
        Schema::table('boleto_lembretes', function (Blueprint $table) {
            $table->date('data_notificacao')->nullable()->index();
            $table->softDeletes();
        });
        DB::table('boleto_agendas')->update(['proxima_notificacao' => DB::raw('proximo_vencimento'), 'dia_notificacao' => DB::raw('dia_vencimento')]);
        DB::table('boleto_lembretes')->update(['data_notificacao' => DB::raw('vencimento')]);
    }

    public function down(): void
    {
        Schema::table('boleto_agendas', fn (Blueprint $table) => $table->dropColumn(['proxima_notificacao', 'dia_notificacao', 'deleted_at']));
        Schema::table('boleto_lembretes', fn (Blueprint $table) => $table->dropColumn(['data_notificacao', 'deleted_at']));
    }
};
