<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-mails criados pelo backoffice PARA o cliente (conta de e-mail do cliente,
 * usada na implantação junto às operadoras). Não confundir com acesso_empresas
 * (login/senha da proposta) nem com credenciais_acesso (portais).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venda_emails_criados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('titular_id')->nullable()->constrained('vendas_titulares')->nullOnDelete();
            $table->string('email');
            $table->string('senha');
            $table->string('observacao', 500)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['venda_id', 'empresa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venda_emails_criados');
    }
};
