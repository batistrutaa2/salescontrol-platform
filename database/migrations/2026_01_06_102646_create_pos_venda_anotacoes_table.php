<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pos_venda_anotacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('venda_id');
            $table->unsignedBigInteger('user_id');
            $table->text('descricao');
            $table->timestamps();

            $table->foreign('empresa_id')
                ->references('id')
                ->on('empresas')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('venda_id')
                ->references('id')
                ->on('vendas')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->index(['empresa_id', 'venda_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_venda_anotacoes');
    }
};
