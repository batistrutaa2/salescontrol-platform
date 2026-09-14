<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boleto_agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('venda_id')->unique()->constrained('vendas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_vencimento');
            $table->date('proximo_vencimento');
            $table->boolean('ativo')->default(true);
            $table->foreignId('configurado_por')->constrained('users');
            $table->timestamps();
            $table->index(['ativo', 'proximo_vencimento']);
        });

        Schema::create('boleto_lembretes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('agenda_id')->constrained('boleto_agendas')->cascadeOnDelete();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->date('competencia');
            $table->date('vencimento');
            $table->timestamp('tratado_em')->nullable();
            $table->foreignId('tratado_por')->nullable()->constrained('users');
            $table->string('observacao', 1000)->nullable();
            $table->timestamps();
            $table->unique(['agenda_id', 'competencia']);
            $table->index(['empresa_id', 'tratado_em', 'vencimento'], 'boleto_lembretes_pendentes_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boleto_lembretes');
        Schema::dropIfExists('boleto_agendas');
    }
};
