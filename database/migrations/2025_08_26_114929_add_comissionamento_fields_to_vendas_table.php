<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->boolean('comissao_paga')->default(false)->after('motivo_pendencia');
            $table->timestamp('data_pagamento_comissao')->nullable()->after('comissao_paga');

            $table->boolean('comissao_estornada')->default(false)->after('data_pagamento_comissao');
            $table->timestamp('data_estorno_comissao')->nullable()->after('comissao_estornada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            Schema::table('vendas', function (Blueprint $table) {
                $table->dropColumn([
                    'comissao_paga',
                    'data_pagamento_comissao',
                    'comissao_estornada',
                    'data_estorno_comissao',
                ]);
            });
        });
    }
};
