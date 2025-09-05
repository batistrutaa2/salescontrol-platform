<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comissao_pagamentos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('vendedor_id');
            $table->char('mes', 7);
            $table->date('data_pagamento');

            // Percentuais vigentes no momento do pagamento
            $table->decimal('percentual_comissao', 5, 2)->default(0);
            $table->decimal('percentual_imposto', 5, 2)->default(10);

            // Totais do pagamento
            $table->decimal('total_bruto', 15, 2)->default(0);
            $table->decimal('total_imposto', 15, 2)->default(0);
            $table->decimal('total_liquido', 15, 2)->default(0);
            $table->decimal('salario', 15, 2)->default(0);
            $table->decimal('total_receber', 15, 2)->default(0);

            $table->unsignedBigInteger('created_by');

            $table->timestamps();

            // índices para buscas rápidas
            $table->index(['empresa_id', 'mes']);
            $table->index('vendedor_id');

            // FKs (ajuste se os nomes divergirem do seu projeto)
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('vendedor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comissao_pagamentos');
    }
};
