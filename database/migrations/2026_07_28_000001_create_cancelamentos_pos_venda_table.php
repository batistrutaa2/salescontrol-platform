<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Painel de Cancelamentos do pós-venda: acompanhamento do cancelamento do plano
 * de saúde anterior do cliente na operadora antiga (serviço concierge). Entrada
 * 100% manual pelo backoffice, sempre vinculada à venda nova.
 * Não confundir com cancelamentos_liminares (kanban da advogada) nem com
 * venda_demandas (processos da tela de detalhe do contrato).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelamentos_pos_venda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->string('escopo', 20);                    // APOLICE | BENEFICIARIO
            $table->string('beneficiario_nome')->nullable(); // obrigatório quando escopo = BENEFICIARIO
            $table->string('via', 30);                       // DIRETO_OPERADORA | LIMINAR
            $table->string('operadora_anterior');            // texto livre: qualquer operadora do mercado
            $table->string('etapa', 30)->default('AGUARDANDO_IMPLANTACAO');
            $table->string('protocolo')->nullable();         // protocolo da operadora, preenchido ao solicitar
            $table->string('observacoes', 2000)->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->date('solicitado_em')->nullable();
            $table->dateTime('concluido_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'etapa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelamentos_pos_venda');
    }
};
