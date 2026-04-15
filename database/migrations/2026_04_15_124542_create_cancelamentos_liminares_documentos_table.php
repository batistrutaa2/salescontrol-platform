<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelamentos_liminares_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cancelamento_liminar_id');
            $table->foreign('cancelamento_liminar_id', 'fk_canc_lim_doc_liminar_id')
                ->references('id')->on('cancelamentos_liminares')
                ->onUpdate('no action')->onDelete('cascade');
            $table->foreignId('empresa_id')->constrained('empresas')->onUpdate('no action')->onDelete('no action');
            $table->enum('tipo_documento', [
                'CARTEIRINHA',
                'CARTAO_CNPJ',
                'COMPROVANTE_PAGAMENTO',
                'CONTRATO_SOCIAL',
                'RETORNO_CANCELAMENTO',
                'RG_CLIENTE',
            ]);
            $table->string('nome_original');
            $table->string('path_s3');
            $table->foreignId('uploaded_by')->constrained('users')->onUpdate('no action')->onDelete('no action');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelamentos_liminares_documentos');
    }
};
