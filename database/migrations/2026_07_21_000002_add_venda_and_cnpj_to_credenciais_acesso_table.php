<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite escopar uma credencial ("acesso empresa") a um contrato específico
 * (venda_id) e etiquetá-la por CNPJ, para a aba de Senhas/Acessos por venda.
 * Mantém o cofre global funcionando: ambas as colunas são nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credenciais_acesso', function (Blueprint $table) {
            $table->foreignId('venda_id')->nullable()->after('operadora_id')
                ->constrained('vendas')->nullOnDelete();
            $table->string('cnpj', 20)->nullable()->after('venda_id');

            $table->index(['empresa_id', 'cnpj']);
        });
    }

    public function down(): void
    {
        Schema::table('credenciais_acesso', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'cnpj']);
            $table->dropConstrainedForeignId('venda_id');
            $table->dropColumn('cnpj');
        });
    }
};
