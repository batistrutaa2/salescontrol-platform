<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->string('cpf_cnpj_normalizado', 14)->nullable()->after('cpf_cnpj');
            $table->index(['empresa_id', 'tabulacao_id', 'cpf_cnpj_normalizado'], 'vendas_renovacao_documento_idx');
        });

        Schema::create('renovacao_oportunidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('documento', 14);
            $table->unsignedBigInteger('venda_referencia_id');
            $table->unsignedBigInteger('vendedor_original_id')->nullable();
            $table->unsignedBigInteger('responsavel_id')->nullable();
            $table->unsignedBigInteger('contato_id')->nullable();
            $table->unsignedBigInteger('lead_vendedor_id')->nullable();
            $table->unsignedBigInteger('nova_venda_id')->nullable();
            $table->string('status', 32)->default('ELEGIVEL');
            $table->date('data_base');
            $table->date('elegivel_desde');
            $table->date('recontato_em')->nullable();
            $table->timestamp('contatada_em')->nullable();
            $table->timestamp('respondida_em')->nullable();
            $table->timestamp('cotacao_solicitada_em')->nullable();
            $table->timestamp('convertida_em')->nullable();
            $table->timestamp('encerrada_em')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'documento'], 'renovacao_empresa_documento_unique');
            $table->index(['empresa_id', 'status', 'recontato_em'], 'renovacao_fila_idx');
            $table->index(['empresa_id', 'responsavel_id', 'status'], 'renovacao_responsavel_idx');
            $table->index('venda_referencia_id');
            $table->index('nova_venda_id');
        });

        Schema::create('renovacao_interacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oportunidade_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('tipo', 40);
            $table->text('observacao')->nullable();
            $table->json('metadados')->nullable();
            $table->timestamps();

            $table->index(['oportunidade_id', 'created_at'], 'renovacao_interacoes_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renovacao_interacoes');
        Schema::dropIfExists('renovacao_oportunidades');
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropIndex('vendas_renovacao_documento_idx');
            $table->dropColumn('cpf_cnpj_normalizado');
        });
    }
};
