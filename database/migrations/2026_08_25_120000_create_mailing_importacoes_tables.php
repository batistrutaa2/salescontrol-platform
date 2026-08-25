<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_importacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tabulacao_id')->nullable()->constrained('tabulacoes')->nullOnDelete();
            $table->string('nome_base');
            $table->string('arquivo_nome')->nullable();
            $table->string('tipo_layout', 30)->default('padrao');
            $table->string('status', 30)->default('EM_ANALISE');
            $table->unsignedInteger('total_itens')->default(0);
            $table->unsignedInteger('total_novos')->default(0);
            $table->unsignedInteger('total_duplicados')->default(0);
            $table->unsignedInteger('total_invalidos')->default(0);
            $table->unsignedInteger('total_importados')->default(0);
            $table->unsignedInteger('total_resolvidos')->default(0);
            $table->timestamp('importados_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status', 'updated_at'], 'mailing_importacoes_pendentes_idx');
        });

        Schema::create('mailing_importacao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailing_importacao_id')->constrained('mailing_importacoes')->cascadeOnDelete();
            $table->unsignedInteger('linha');
            $table->string('cpf', 32)->nullable();
            $table->string('nome')->nullable();
            $table->json('payload')->nullable();
            $table->string('classificacao', 30);
            $table->string('motivo')->nullable();
            $table->foreignId('contato_existente_id')->nullable()->constrained('contatos')->nullOnDelete();
            $table->foreignId('contato_importado_id')->nullable()->constrained('contatos')->nullOnDelete();
            $table->string('resolucao', 40)->nullable();
            $table->foreignId('resolvido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolvido_em')->nullable();
            $table->timestamps();

            $table->unique(['mailing_importacao_id', 'linha'], 'mailing_importacao_linha_unique');
            $table->index(['mailing_importacao_id', 'classificacao', 'resolvido_em'], 'mailing_importacao_itens_fila_idx');
            $table->index(['mailing_importacao_id', 'cpf'], 'mailing_importacao_itens_cpf_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_importacao_itens');
        Schema::dropIfExists('mailing_importacoes');
    }
};
