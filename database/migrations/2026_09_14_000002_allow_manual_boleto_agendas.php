<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['boleto_agendas', 'boleto_lembretes'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('venda_id')->nullable()->change();
                $table->string('nome_cliente', 160)->nullable();
                $table->string('referencia', 160)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (DB::table('boleto_agendas')->whereNull('venda_id')->exists()) {
            throw new RuntimeException('Existem lembretes sem contrato. Preserve esses registros antes de reverter a migration.');
        }
        foreach (['boleto_lembretes', 'boleto_agendas'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('venda_id')->nullable(false)->change();
                $table->dropColumn(['nome_cliente', 'referencia']);
            });
        }
    }
};
