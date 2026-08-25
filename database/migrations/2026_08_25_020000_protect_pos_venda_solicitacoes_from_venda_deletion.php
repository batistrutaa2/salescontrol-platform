<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->dropForeign(['venda_id']);
            $table->foreign('venda_id')->references('id')->on('vendas')
                ->onUpdate('no action')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->dropForeign(['venda_id']);
            $table->foreign('venda_id')->references('id')->on('vendas')->cascadeOnDelete();
        });
    }
};
