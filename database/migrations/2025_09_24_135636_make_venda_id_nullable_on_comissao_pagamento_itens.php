<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('comissao_pagamento_itens', 'venda_id')) {
            try {
                Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
                    $table->unsignedBigInteger('venda_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                DB::statement('ALTER TABLE comissao_pagamento_itens MODIFY venda_id BIGINT UNSIGNED NULL;');
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
                $table->unsignedBigInteger('venda_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {}
    }
};
