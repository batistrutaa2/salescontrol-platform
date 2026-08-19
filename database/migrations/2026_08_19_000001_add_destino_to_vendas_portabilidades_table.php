<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas_portabilidades', function (Blueprint $table) {
            $table->unsignedBigInteger('operadora_destino_id')->nullable()->after('numero_carteirinha');
            $table->unsignedBigInteger('plano_destino_id')->nullable()->after('operadora_destino_id');

            $table->foreign('operadora_destino_id')
                ->references('id')->on('operadoras')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('plano_destino_id')
                ->references('id')->on('planos')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::table('vendas_portabilidades', function (Blueprint $table) {
            $table->dropForeign(['plano_destino_id']);
            $table->dropForeign(['operadora_destino_id']);
            $table->dropColumn(['plano_destino_id', 'operadora_destino_id']);
        });
    }
};
