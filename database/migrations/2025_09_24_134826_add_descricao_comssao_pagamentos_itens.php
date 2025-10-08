<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            // Se não existir, adiciona a FK para ajustes
            if (!Schema::hasColumn('comissao_pagamento_itens', 'ajuste_id')) {
                $table->unsignedBigInteger('ajuste_id')->nullable()->after('venda_id');
                $table->index('ajuste_id', 'cpi_ajuste_id_index');
            }

            // Flag se é item adicional (ajuste)
            if (!Schema::hasColumn('comissao_pagamento_itens', 'is_adicional')) {
                $table->boolean('is_adicional')->default(false)->after('ajuste_id');
            }

            // Tipo do lançamento (para ajustes e também para futuros usos)
            if (!Schema::hasColumn('comissao_pagamento_itens', 'tipo_lancamento')) {
                $table->enum('tipo_lancamento', ['MOTIVACIONAL','AJUSTE','BONUS','OUTRO'])
                      ->default('OUTRO')
                      ->after('is_adicional');
            }

            // Descrição do ajuste (opcional)
            if (!Schema::hasColumn('comissao_pagamento_itens', 'descricao')) {
                $table->string('descricao', 255)->nullable()->after('tipo_lancamento');
            }
        });

        // Adiciona a FK de ajuste_id se ainda não existir
        // (MySQL não tem check nativo em Blueprint, então inspecionamos information_schema)
        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'comissao_pagamento_itens'
              AND COLUMN_NAME = 'ajuste_id'
              AND REFERENCED_TABLE_NAME = 'lancamentos_debito_credito'
        ");

        if (!$fkExists) {
            Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
                $table->foreign('ajuste_id', 'cpi_ajuste_id_fk')
                      ->references('id')->on('lancamentos_debito_credito')
                      ->onDelete('restrict');
            });
        }

        // Opcional: garantir que o mesmo ajuste não seja pago duas vezes
        // (só crie se fizer sentido para o seu fluxo)
        $uniqueAjusteExists = DB::selectOne("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'comissao_pagamento_itens'
              AND INDEX_NAME = 'unique_pagamento_ajuste'
        ");
        if (!$uniqueAjusteExists) {
            Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
                $table->unique('ajuste_id', 'unique_pagamento_ajuste');
            });
        }
    }

    public function down(): void
    {
        // Reverte com cuidado (opcional)
        Schema::table('comissao_pagamento_itens', function (Blueprint $table) {
            if (Schema::hasColumn('comissao_pagamento_itens', 'descricao')) {
                $table->dropColumn('descricao');
            }
            if (Schema::hasColumn('comissao_pagamento_itens', 'tipo_lancamento')) {
                $table->dropColumn('tipo_lancamento');
            }
            if (Schema::hasColumn('comissao_pagamento_itens', 'is_adicional')) {
                $table->dropColumn('is_adicional');
            }

            // Drop FK + índice/unique de ajuste_id se existirem
            // (ignora erros se não existirem)
            try { $table->dropForeign('cpi_ajuste_id_fk'); } catch (\Throwable $e) {}
            try { $table->dropUnique('unique_pagamento_ajuste'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('comissao_pagamento_itens', 'ajuste_id')) {
                $table->dropIndex('cpi_ajuste_id_index');
                $table->dropColumn('ajuste_id');
            }
        });
    }
};
