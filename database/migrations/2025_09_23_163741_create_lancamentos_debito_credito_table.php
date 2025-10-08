<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos_debito_credito', function (Blueprint $table) {
            $table->id();

            // FKs
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('vendedor_id');
            $table->unsignedBigInteger('created_by');

            // Dados do lançamento
            $table->enum('natureza', ['DEBITO', 'CREDITO']);          
            $table->string('categoria', 50);                         
            $table->decimal('valor_bruto', 15, 2);
            $table->decimal('imposto_perc', 5, 2)->default(0);         
            $table->decimal('imposto_valor', 15, 2)->default(0);       
            $table->decimal('valor_liquido', 15, 2);                   
            $table->char('mes', 7);                                    
            $table->text('descricao')->nullable();

            // Fluxo/baixa opcional
            $table->enum('status', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->unsignedBigInteger('comissao_pagamento_id')->nullable(); 
            $table->timestamp('pago_em')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['empresa_id', 'mes']);
            $table->index(['empresa_id', 'vendedor_id', 'mes']);

            // FKs corretas
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('vendedor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('comissao_pagamento_id')->references('id')->on('comissao_pagamentos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_debito_credito');
    }
};
