<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            if (!Schema::hasColumn('comissao_pagamento_itens', 'ajuste_id')) {
                $table->unsignedBigInteger('ajuste_id')->nullable()->after('venda_id');
                $table->foreign('ajuste_id')
                      ->references('id')->on('lancamentos_debito_credito')
                      ->onDelete('restrict');
                $table->unique('ajuste_id', 'comissao_pagamento_itens_ajuste_id_unique');
            }
        });
    }

    public function down(): void {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            if (Schema::hasColumn('comissao_pagamento_itens', 'ajuste_id')) {
                $table->dropUnique('comissao_pagamento_itens_ajuste_id_unique');
                $table->dropForeign(['ajuste_id']);
                $table->dropColumn('ajuste_id');
            }
        });
    }
};
