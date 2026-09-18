<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitação avulsa: clientes da carteira que não foram (nem serão)
 * cadastrados como contrato. Sem `venda_id`, a identificação do cliente fica
 * nos campos `cliente_*` da própria solicitação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->dropForeign(['venda_id']);
        });

        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('venda_id')->nullable()->change();
            $table->string('cliente_nome')->nullable()->after('venda_id');
            $table->string('cliente_documento', 20)->nullable()->after('cliente_nome');
            $table->string('cliente_telefone', 20)->nullable()->after('cliente_documento');
            $table->string('cliente_operadora', 100)->nullable()->after('cliente_telefone');

            $table->foreign('venda_id')->references('id')->on('vendas')
                ->onUpdate('no action')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->dropForeign(['venda_id']);
        });

        Schema::table('pos_venda_solicitacoes', function (Blueprint $table) {
            $table->dropColumn(['cliente_nome', 'cliente_documento', 'cliente_telefone', 'cliente_operadora']);
            $table->unsignedBigInteger('venda_id')->nullable(false)->change();

            $table->foreign('venda_id')->references('id')->on('vendas')
                ->onUpdate('no action')->onDelete('restrict');
        });
    }
};
