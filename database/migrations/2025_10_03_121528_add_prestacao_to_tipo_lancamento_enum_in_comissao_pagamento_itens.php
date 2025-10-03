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
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // Adiciona PRESTACAO ao enum tipo_lancamento
            $table->enum('tipo_lancamento', [
                'MOTIVACIONAL',
                'AJUSTE',
                'BONUS',
                'OUTRO',
                'ANGARIACAO',
                'PRESTACAO'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // Remove PRESTACAO do enum
            $table->enum('tipo_lancamento', [
                'MOTIVACIONAL',
                'AJUSTE',
                'BONUS',
                'OUTRO',
                'ANGARIACAO'
            ])->change();
        });
    }
};
