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
        Schema::create('vendas_historico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('venda_id');
            $table->unsignedBigInteger('contato_corretor_id')->nullable();
            $table->unsignedBigInteger('user_id'); // quem alterou
            $table->unsignedBigInteger('tabulacao_anterior_id')->nullable();
            $table->unsignedBigInteger('tabulacao_nova_id');
            $table->text('observacao')->nullable();
            $table->string('motivo_pendencia')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('empresa_id')
                ->references('id')
                ->on('empresas')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('venda_id')
                ->references('id')
                ->on('vendas')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('tabulacao_anterior_id')
                ->references('id')
                ->on('tabulacoes')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign('tabulacao_nova_id')
                ->references('id')
                ->on('tabulacoes')
                ->onUpdate('no action')
                ->onDelete('no action');

            // Indexes
            $table->index(['empresa_id', 'venda_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendas_historico');
    }
};
