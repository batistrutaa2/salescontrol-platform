<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // recria o enum com os valores existentes + o novo
            $table->enum('tipo_lancamento', [
                'MOTIVACIONAL',
                'AJUSTE',
                'BONUS',
                'OUTRO',
                'ANGARIACAO'
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            $table->enum('tipo_lancamento', [
                'MOTIVACIONAL',
                'AJUSTE',
                'BONUS',
                'OUTRO',
            ])->change();
        });
    }
};

