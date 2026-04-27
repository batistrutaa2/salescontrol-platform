<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('produto_interesse_id');
            $table->unsignedBigInteger('pessoa_id')->nullable();
            $table->unsignedBigInteger('empresa_lemit_id')->nullable();

            $table->enum('cliente_tipo', ['PF', 'PJ']);
            $table->string('cpf_cnpj', 20);
            $table->string('nome', 150);
            $table->string('email', 120)->nullable();
            $table->string('telefone', 30)->nullable();

            $table->enum('status', [
                'NOVO_CLIENTE',
                'PROSPECTADO',
                'SEM_CONTATO',
                'NEGOCIANDO',
                'FOLLOW_UP',
                'DOCUMENTACAO',
            ])->default('NOVO_CLIENTE');

            $table->enum('origem', ['BASE_SAUDE', 'MANUAL']);
            $table->unsignedInteger('ordem_kanban')->default(0);
            $table->timestamp('data_ultima_movimentacao')->useCurrent();
            $table->text('observacoes')->nullable();
            $table->timestamp('convertido_em')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'status', 'ordem_kanban']);
            $table->index(['empresa_id', 'cpf_cnpj']);
            $table->index(['empresa_id', 'user_id']);

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('produto_interesse_id')->references('id')->on('lk_beneficios_produtos')
                ->onUpdate('no action')->onDelete('no action');
            $table->index(['empresa_id', 'cpf_cnpj', 'produto_interesse_id'], 'lkb_leads_cpf_produto_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_leads');
    }
};
