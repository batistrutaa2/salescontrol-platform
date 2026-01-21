<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adiciona campos para o novo layout PME e campo layout_venda para diferenciar propostas antigas e novas
     */
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            // Campo para diferenciar layout antigo/novo no backoffice
            $table->enum('layout_venda', ['ANTIGO', 'NOVO'])->default('ANTIGO')->after('angariacao_status');

            // Tipo de contrato (PF, PME, etc)
            $table->string('tipo_contrato', 20)->nullable()->after('layout_venda');

            // Campos PME - Dados da empresa
            $table->enum('tipo_empresa', ['MEI', 'ME', 'EPP', 'LTDA', 'SA', 'EIRELI', 'SLU'])->nullable()->after('tipo_contrato');
            $table->date('data_abertura')->nullable()->after('tipo_empresa');

            // Referência à operadora (mantendo campo texto para compatibilidade)
            $table->unsignedBigInteger('operadora_id')->nullable()->after('operadora');

            // Portabilidade
            $table->enum('portabilidade_status', ['SIM', 'NAO'])->default('NAO')->after('angariacao_status');
            $table->integer('qtd_portabilidade')->default(0)->after('portabilidade_status');

            // Foreign key para operadora
            $table->foreign('operadora_id')
                ->references('id')
                ->on('operadoras')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropForeign(['operadora_id']);
            $table->dropColumn([
                'layout_venda',
                'tipo_contrato',
                'tipo_empresa',
                'data_abertura',
                'operadora_id',
                'portabilidade_status',
                'qtd_portabilidade',
            ]);
        });
    }
};
