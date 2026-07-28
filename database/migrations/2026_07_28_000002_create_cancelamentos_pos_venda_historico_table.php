<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timeline auditável do Painel de Cancelamentos: cada mudança de etapa,
 * responsável, protocolo ou anotação vira uma linha (valores como labels).
 * Motivo de desistência também mora aqui — não há coluna na tabela principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelamentos_pos_venda_historico', function (Blueprint $table) {
            $table->id();
            // Nome default da constraint passaria do limite de 64 chars do MySQL.
            $table->foreignId('cancelamento_pos_venda_id')
                ->constrained(table: 'cancelamentos_pos_venda', indexName: 'fk_canc_pv_hist_cancelamento_id')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('campo_alterado'); // 'etapa' | 'responsavel' | 'protocolo' | 'observacoes' | 'anotacao'
            $table->string('valor_anterior')->nullable();
            $table->string('valor_novo')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelamentos_pos_venda_historico');
    }
};
