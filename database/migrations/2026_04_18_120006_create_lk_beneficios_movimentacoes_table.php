<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lk_beneficios_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_id');
            $table->unsignedBigInteger('beneficiario_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->enum('tipo', [
                'INCLUSAO',
                'EXCLUSAO',
                'REAJUSTE',
                'ALTERACAO_DADOS',
                'REATIVACAO',
                'CANCELAMENTO',
            ]);
            $table->text('descricao');
            $table->decimal('valor_anterior', 12, 2)->nullable();
            $table->decimal('valor_novo', 12, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['contrato_id', 'tipo']);
            $table->index('beneficiario_id');

            $table->foreign('contrato_id')->references('id')->on('lk_beneficios_contratos')
                ->onUpdate('no action')->onDelete('cascade');
            $table->foreign('beneficiario_id')->references('id')->on('lk_beneficios_beneficiarios')
                ->onUpdate('no action')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lk_beneficios_movimentacoes');
    }
};
