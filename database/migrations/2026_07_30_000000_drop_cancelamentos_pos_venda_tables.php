<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * O Painel de Cancelamentos foi substituído pela Central de Solicitações do
 * Pós-Venda (pos_venda_solicitacoes). As tabelas antigas saem sem migração de
 * dados: o painel nunca chegou a ser operado em produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cancelamentos_pos_venda_historico');
        Schema::dropIfExists('cancelamentos_pos_venda');
    }

    public function down(): void
    {
        // Sem rollback: as migrations de criação foram removidas junto com o módulo.
    }
};
