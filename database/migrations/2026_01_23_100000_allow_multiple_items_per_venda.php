<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Permite múltiplos lançamentos por venda (COMISSAO + ANGARIACAO).
     * - Remove UNIQUE de venda_id
     * - Cria índice composto: mesma venda pode ter 1 COMISSAO + 1 ANGARIACAO
     * - Adiciona valor 'COMISSAO' ao enum tipo_lancamento
     */
    public function up(): void
    {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // 1. Remove a foreign key de venda_id primeiro (necessário para dropar o unique)
            $table->dropForeign(['venda_id']);
        });

        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // 2. Remove UNIQUE constraint de venda_id
            $table->dropUnique(['venda_id']);
        });

        // 3. Adiciona COMISSAO ao enum tipo_lancamento
        DB::statement("ALTER TABLE comissao_pagamento_itens
            MODIFY tipo_lancamento ENUM('MOTIVACIONAL','AJUSTE','BONUS','OUTRO','ANGARIACAO','PRESTACAO','COMISSAO')
            DEFAULT 'OUTRO'");

        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // 4. Cria índice composto para permitir apenas 1 item de cada tipo por venda
            $table->unique(['venda_id', 'tipo_lancamento'], 'unique_venda_tipo');

            // 5. Recria a foreign key de venda_id
            $table->foreign('venda_id')
                  ->references('id')->on('vendas')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // Remove a foreign key
            $table->dropForeign(['venda_id']);

            // Remove índice composto
            $table->dropUnique('unique_venda_tipo');
        });

        // Remove COMISSAO do enum
        DB::statement("ALTER TABLE comissao_pagamento_itens
            MODIFY tipo_lancamento ENUM('MOTIVACIONAL','AJUSTE','BONUS','OUTRO','ANGARIACAO','PRESTACAO')
            DEFAULT 'OUTRO'");

        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // Restaura UNIQUE simples em venda_id
            $table->unique('venda_id');

            // Recria a foreign key
            $table->foreign('venda_id')
                  ->references('id')->on('vendas')
                  ->onDelete('restrict');
        });
    }
};
