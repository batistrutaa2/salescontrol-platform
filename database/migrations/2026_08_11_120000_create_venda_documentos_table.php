<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venda_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venda_id')->constrained('vendas')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_upload_id', 64)->nullable();
            $table->string('nome_original');
            $table->string('nome_remoto');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('tamanho');
            $table->char('sha256', 64);
            $table->string('caminho_temporario');
            $table->string('diretorio_remoto');
            $table->string('caminho_remoto');
            $table->string('status', 24)->default('AGUARDANDO');
            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->text('erro')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('expira_em')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->unique(['venda_id', 'client_upload_id']);
            $table->index(['empresa_id', 'status']);
            $table->index(['status', 'expira_em']);
        });

        Schema::table('vendas', function (Blueprint $table) {
            $table->string('documentacao_status', 24)->default('PENDENTE')->after('vitalicio_ativo');
            $table->string('documentacao_diretorio')->nullable()->after('documentacao_status');
            $table->unique(['empresa_id', 'documentacao_diretorio'], 'vendas_empresa_documentacao_dir_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropUnique('vendas_empresa_documentacao_dir_unique');
            $table->dropColumn(['documentacao_status', 'documentacao_diretorio']);
        });
        Schema::dropIfExists('venda_documentos');
    }
};
