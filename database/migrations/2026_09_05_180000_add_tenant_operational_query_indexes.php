<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contatos', function (Blueprint $table): void {
            $table->index(['empresa_id', 'status'], 'contatos_empresa_status_idx');
            $table->index(['empresa_id', 'cpf'], 'contatos_empresa_cpf_idx');
        });

        Schema::table('contatos_corretores', function (Blueprint $table): void {
            $table->index(['empresa_id', 'contato_id', 'user_id'], 'cc_empresa_contato_usuario_idx');
            $table->index(['empresa_id', 'user_id', 'tabulacao_id', 'updated_at'], 'cc_empresa_funil_idx');
        });

        Schema::table('vendas', function (Blueprint $table): void {
            $table->index(['empresa_id', 'contato_id'], 'vendas_empresa_contato_idx');
            $table->index(['empresa_id', 'user_id', 'created_at'], 'vendas_empresa_vendedor_periodo_idx');
            $table->index(['empresa_id', 'backoffice_id', 'tabulacao_id'], 'vendas_empresa_backoffice_status_idx');
        });

        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->index(['empresa_id', 'contato_id'], 'agendamentos_empresa_contato_idx');
            $table->index(['empresa_id', 'user_id', 'horario_agendamento'], 'agendamentos_empresa_usuario_horario_idx');
        });

        Schema::table('preditiva', function (Blueprint $table): void {
            $table->index(['empresa_id', 'contato_id'], 'preditiva_empresa_contato_idx');
            $table->index(['empresa_id', 'status', 'user_id', 'data_atribuicao'], 'preditiva_empresa_fila_lock_idx');
        });

        Schema::table('ligacoes', function (Blueprint $table): void {
            $table->index(['empresa_id', 'user_id', 'created_at'], 'ligacoes_empresa_usuario_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ligacoes', fn (Blueprint $table) => $table->dropIndex('ligacoes_empresa_usuario_periodo_idx'));
        Schema::table('preditiva', function (Blueprint $table): void {
            $table->dropIndex('preditiva_empresa_contato_idx');
            $table->dropIndex('preditiva_empresa_fila_lock_idx');
        });
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropIndex('agendamentos_empresa_contato_idx');
            $table->dropIndex('agendamentos_empresa_usuario_horario_idx');
        });
        Schema::table('vendas', function (Blueprint $table): void {
            $table->dropIndex('vendas_empresa_contato_idx');
            $table->dropIndex('vendas_empresa_vendedor_periodo_idx');
            $table->dropIndex('vendas_empresa_backoffice_status_idx');
        });
        Schema::table('contatos_corretores', function (Blueprint $table): void {
            $table->dropIndex('cc_empresa_contato_usuario_idx');
            $table->dropIndex('cc_empresa_funil_idx');
        });
        Schema::table('contatos', function (Blueprint $table): void {
            $table->dropIndex('contatos_empresa_status_idx');
            $table->dropIndex('contatos_empresa_cpf_idx');
        });
    }
};
