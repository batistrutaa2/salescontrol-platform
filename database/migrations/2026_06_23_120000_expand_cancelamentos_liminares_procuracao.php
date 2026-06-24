<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Novos campos da procuração — todos nullable no schema;
        // a obrigatoriedade é validada na abertura do processo (controller).
        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->string('nome_empresa')->nullable()->after('nome_contrato');
            $table->string('cnpj', 20)->nullable()->after('nome_empresa');
            $table->string('protocolo_cancelamento')->nullable()->after('cnpj');
            $table->string('email_procuracao')->nullable()->after('protocolo_cancelamento');

            $table->date('data_fim_plano')->nullable()->after('email_procuracao');
            $table->date('data_contratacao')->nullable()->after('data_fim_plano');
            $table->date('data_solicitacao_cancelamento')->nullable()->after('data_contratacao');
            $table->date('data_ultimo_pagamento_boleto')->nullable()->after('data_solicitacao_cancelamento');
            $table->date('cobertura_comprovante_inicio')->nullable()->after('data_ultimo_pagamento_boleto');
            $table->date('cobertura_comprovante_fim')->nullable()->after('cobertura_comprovante_inicio');
            $table->date('data_vencimento_boleto_1')->nullable()->after('cobertura_comprovante_fim');
            $table->date('data_vencimento_boleto_2')->nullable()->after('data_vencimento_boleto_1');
        });

        // fase: enum -> string (validação passa a viver no controller, via `in:`).
        // Evita o atrito de ALTER ENUM e mantém o conjunto de colunas portável.
        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->string('fase', 50)->nullable()->default('CANCELAMENTO_ABERTO')->change();
        });

        // Remapeia os valores antigos para o novo fluxo do kanban.
        DB::table('cancelamentos_liminares')
            ->whereIn('fase', ['ASSINATURA_PROCURACAO', 'ENVIO_DOCUMENTOS'])
            ->update(['fase' => 'CANCELAMENTO_ABERTO']);

        DB::table('cancelamentos_liminares')
            ->where('fase', 'AGUARDANDO_LIMINAR')
            ->update(['fase' => 'AGUARDANDO_ASSINATURA']);

        // Registros sem fase definida entram como recém-abertos.
        DB::table('cancelamentos_liminares')
            ->whereNull('fase')
            ->update(['fase' => 'CANCELAMENTO_ABERTO']);
    }

    public function down(): void
    {
        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->dropColumn([
                'nome_empresa',
                'cnpj',
                'protocolo_cancelamento',
                'email_procuracao',
                'data_fim_plano',
                'data_contratacao',
                'data_solicitacao_cancelamento',
                'data_ultimo_pagamento_boleto',
                'cobertura_comprovante_inicio',
                'cobertura_comprovante_fim',
                'data_vencimento_boleto_1',
                'data_vencimento_boleto_2',
            ]);
        });

        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->enum('fase', [
                'ASSINATURA_PROCURACAO',
                'ENVIO_DOCUMENTOS',
                'AGUARDANDO_LIMINAR',
                'AGUARDANDO_RETORNO_OPERADORA',
                'LIMINAR_CONCEDIDA',
            ])->nullable()->default(null)->change();
        });
    }
};
