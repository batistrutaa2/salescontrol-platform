<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciais_acesso_historico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            // nullable + set null: ao excluir a credencial, o histórico (auditoria)
            // sobrevive como trilha imutável, apenas desvinculado do registro apagado.
            $table->unsignedBigInteger('credencial_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('acao', ['CRIACAO', 'EDICAO', 'EXCLUSAO']);
            $table->string('campo', 50)->nullable();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('credencial_id');
            $table->index('empresa_id');

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('credencial_id')->references('id')->on('credenciais_acesso')
                ->onUpdate('no action')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciais_acesso_historico');
    }
};
