<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adiciona campos para controle separado de pagamento de angariação.
     * Agora uma venda pode ter comissão e angariação pagas separadamente.
     */
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->boolean('angariacao_paga')->default(false)->after('comissao_paga');
            $table->dateTime('data_pagamento_angariacao')->nullable()->after('data_pagamento_comissao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropColumn(['angariacao_paga', 'data_pagamento_angariacao']);
        });
    }
};
