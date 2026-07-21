<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dá granularidade aos processos de pós-venda (venda_demandas):
 * - por titular (e-mail de implantação, DS, cancelamento por pessoa) via titular_id
 * - por empresa/contrato (senhas/acessos) via cnpj
 * - contexto do cancelamento na operadora anterior via operadora_anterior_id
 * - quem sinalizou (VENDEDOR/BACKOFFICE/SISTEMA) via origem
 * - payload flexível (ds_tipo, numero_carteirinha, portal...) via meta
 * - vínculo opcional com o cofre de credenciais via credencial_id
 *
 * Tudo nullable/aditivo — não afeta as demandas já existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venda_demandas', function (Blueprint $table) {
            $table->foreignId('titular_id')->nullable()->after('venda_id')
                ->constrained('vendas_titulares')->nullOnDelete();
            $table->string('cnpj', 20)->nullable()->after('titular_id');
            $table->foreignId('operadora_anterior_id')->nullable()->after('cnpj')
                ->constrained('operadoras')->nullOnDelete();
            $table->enum('origem', ['VENDEDOR', 'BACKOFFICE', 'SISTEMA'])
                ->default('BACKOFFICE')->after('created_by');
            $table->json('meta')->nullable()->after('descricao');
            $table->foreignId('credencial_id')->nullable()->after('meta')
                ->constrained('credenciais_acesso')->nullOnDelete();

            $table->index(['venda_id', 'tipo']);
            $table->index(['venda_id', 'titular_id']);
            $table->index(['empresa_id', 'cnpj']);
        });
    }

    public function down(): void
    {
        Schema::table('venda_demandas', function (Blueprint $table) {
            $table->dropIndex(['venda_id', 'tipo']);
            $table->dropIndex(['venda_id', 'titular_id']);
            $table->dropIndex(['empresa_id', 'cnpj']);

            $table->dropConstrainedForeignId('titular_id');
            $table->dropConstrainedForeignId('operadora_anterior_id');
            $table->dropConstrainedForeignId('credencial_id');
            $table->dropColumn(['cnpj', 'origem', 'meta']);
        });
    }
};
