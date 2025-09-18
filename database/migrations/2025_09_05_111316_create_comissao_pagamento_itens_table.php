<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comissao_pagamento_itens', function (Blueprint $table) {
            $table->id();

            // FKs
            $table->unsignedBigInteger('comissao_pagamento_id'); // header
            $table->unsignedBigInteger('venda_id');              // venda incluída no pagamento

            // Snapshot dos valores usados no cálculo (congelados no momento do pagamento)
            $table->decimal('valor_contrato', 15, 2)->default(0);
            $table->decimal('percentual', 5, 2)->default(0);     // % comissão aplicado
            $table->decimal('imposto_perc', 5, 2)->default(10);  // % imposto aplicado

            $table->decimal('bruto', 15, 2)->default(0);
            $table->decimal('imposto_valor', 15, 2)->default(0);
            $table->decimal('liquido', 15, 2)->default(0);

            $table->timestamps();

            // Índices
            $table->index('comissao_pagamento_id');
            $table->unique('venda_id'); // impede pagar a mesma venda mais de uma vez

            // FKs (ajuste nomes de tabelas se forem diferentes no seu projeto)
            $table->foreign('comissao_pagamento_id')
                  ->references('id')->on('comissao_pagamentos')
                  ->onDelete('cascade'); // ao apagar o header, apaga os itens

            $table->foreign('venda_id')
                  ->references('id')->on('vendas')
                  ->onDelete('restrict'); // evita deletar venda já paga (ajuste se preferir)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comissao_pagamento_itens');
    }
};
