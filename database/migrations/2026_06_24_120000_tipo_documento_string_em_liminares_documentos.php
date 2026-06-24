<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tipo_documento deixa de ser enum e vira string: novos tipos passam a
        // ser adicionados sem ALTER de enum; a validação vive no controller.
        Schema::table('cancelamentos_liminares_documentos', function (Blueprint $table) {
            $table->string('tipo_documento', 40)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cancelamentos_liminares_documentos', function (Blueprint $table) {
            $table->enum('tipo_documento', [
                'CARTEIRINHA',
                'CARTAO_CNPJ',
                'COMPROVANTE_PAGAMENTO',
                'CONTRATO_SOCIAL',
                'RETORNO_CANCELAMENTO',
                'RG_CLIENTE',
            ])->change();
        });
    }
};
