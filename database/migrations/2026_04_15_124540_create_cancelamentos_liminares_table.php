<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelamentos_liminares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onUpdate('no action')->onDelete('no action');
            $table->foreignId('venda_id')->constrained('vendas')->onUpdate('no action')->onDelete('no action');
            $table->enum('beneficiario_tipo', ['TITULAR', 'DEPENDENTE']);
            $table->unsignedBigInteger('beneficiario_id');
            $table->string('nome_contrato');
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->onUpdate('no action')->onDelete('no action');
            $table->enum('status', ['NAO_INICIADA', 'EM_EXECUCAO', 'BLOQUEADA', 'CONCLUIDA'])->default('NAO_INICIADA');
            $table->enum('fase', [
                'ASSINATURA_PROCURACAO',
                'ENVIO_DOCUMENTOS',
                'AGUARDANDO_LIMINAR',
                'AGUARDANDO_RETORNO_OPERADORA',
                'LIMINAR_CONCEDIDA',
            ])->nullable();
            $table->date('data_envio')->nullable();
            $table->enum('status_honorarios', ['PENDENTE', 'PAGO', 'NAO_SE_APLICA'])->default('PENDENTE');
            $table->enum('status_recebimento', ['BOLETO_GERADO', 'PAGO', 'PENDENTE'])->default('PENDENTE');
            $table->decimal('valor_recebimento', 15, 2)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelamentos_liminares');
    }
};
