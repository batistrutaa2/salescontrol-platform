<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sinal do vendedor no cadastro: este titular precisa que cancelemos o plano
 * anterior? (a operadora anterior já é capturada em operadora_anterior_id).
 * A partir desse sinal nasce o processo de cancelamento (venda_demandas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas_titulares', function (Blueprint $table) {
            $table->boolean('precisa_cancelamento')->default(false)->after('operadora_anterior_id');
        });
    }

    public function down(): void
    {
        Schema::table('vendas_titulares', function (Blueprint $table) {
            $table->dropColumn('precisa_cancelamento');
        });
    }
};
