<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Novo fluxo de status do kanban:
     *   Cancelamento Aberto → Procuração enviada → Procuração assinada → Liminar concedida.
     *
     * A coluna `fase` já é string (migration 2026_06_23), então basta remapear os dados.
     * CANCELAMENTO_ABERTO e LIMINAR_CONCEDIDA permanecem.
     */
    public function up(): void
    {
        DB::table('cancelamentos_liminares')
            ->where('fase', 'AGUARDANDO_ASSINATURA')
            ->update(['fase' => 'PROCURACAO_ENVIADA']);

        DB::table('cancelamentos_liminares')
            ->where('fase', 'AGUARDANDO_RETORNO_OPERADORA')
            ->update(['fase' => 'PROCURACAO_ASSINADA']);
    }

    public function down(): void
    {
        DB::table('cancelamentos_liminares')
            ->where('fase', 'PROCURACAO_ENVIADA')
            ->update(['fase' => 'AGUARDANDO_ASSINATURA']);

        DB::table('cancelamentos_liminares')
            ->where('fase', 'PROCURACAO_ASSINADA')
            ->update(['fase' => 'AGUARDANDO_RETORNO_OPERADORA']);
    }
};
