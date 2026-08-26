<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_reservatorio_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('contato_id')->constrained('contatos')->restrictOnDelete();
            $table->string('origem', 30);
            $table->string('status', 20)->default('DISPONIVEL');
            $table->foreignId('entrou_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entrou_em');
            $table->foreignId('distribuido_para')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('distribuido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('distribuido_em')->nullable();
            $table->string('bloqueado_motivo')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'contato_id'], 'reservatorio_empresa_contato_unique');
            $table->index(['empresa_id', 'status', 'entrou_em'], 'reservatorio_fila_idx');
        });

        Schema::create('lead_reservatorio_estrategias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->string('nome');
            $table->json('condicoes');
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empresa_id', 'ativo', 'nome'], 'reservatorio_estrategias_idx');
        });

        Schema::create('lead_reservatorio_execucoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('estrategia_id')->nullable()->constrained('lead_reservatorio_estrategias')->nullOnDelete();
            $table->string('tipo', 30);
            $table->string('status', 20);
            $table->json('filtros_snapshot')->nullable();
            $table->json('distribuicoes')->nullable();
            $table->string('semente', 64)->nullable();
            $table->unsignedInteger('total_solicitado')->default(0);
            $table->unsignedInteger('total_executado')->default(0);
            $table->unsignedInteger('total_ignorado')->default(0);
            $table->foreignId('vendedor_origem_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('chave_idempotencia')->nullable()->unique();
            $table->timestamp('executada_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'tipo', 'created_at'], 'reservatorio_execucoes_idx');
        });

        Schema::create('lead_reservatorio_execucao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execucao_id')->constrained('lead_reservatorio_execucoes')->cascadeOnDelete();
            $table->foreignId('reservatorio_item_id')->constrained('lead_reservatorio_itens')->restrictOnDelete();
            $table->foreignId('contato_id')->constrained('contatos')->restrictOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resultado', 30);
            $table->string('motivo')->nullable();
            $table->timestamps();

            $table->unique(['execucao_id', 'contato_id'], 'reservatorio_execucao_contato_unique');
            $table->index(['reservatorio_item_id', 'resultado'], 'reservatorio_execucao_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_reservatorio_execucao_itens');
        Schema::dropIfExists('lead_reservatorio_execucoes');
        Schema::dropIfExists('lead_reservatorio_estrategias');
        Schema::dropIfExists('lead_reservatorio_itens');
    }
};
