<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cancelamentos_liminares_documentos')
            ->where('tipo_documento', 'AUDIO_HAPVIDA')
            ->update(['tipo_documento' => 'AUDIO_OPERADORA']);

        DB::table('venda_demandas')
            ->where('tipo', 'CANCELAMENTO_QUALICORP')
            ->update(['tipo' => 'CANCELAMENTO_INTERMEDIADORA']);

        DB::table('pos_venda_demanda_templates')
            ->where('tipo', 'CANCELAMENTO_QUALICORP')
            ->update(['tipo' => 'CANCELAMENTO_INTERMEDIADORA']);
        DB::table('pos_venda_demanda_templates')
            ->where('titulo', 'Cancelar plano Qualicorp')
            ->update(['titulo' => 'Cancelar vínculo com intermediadora']);
    }

    public function down(): void
    {
        DB::table('cancelamentos_liminares_documentos')
            ->where('tipo_documento', 'AUDIO_OPERADORA')
            ->update(['tipo_documento' => 'AUDIO_HAPVIDA']);

        DB::table('venda_demandas')
            ->where('tipo', 'CANCELAMENTO_INTERMEDIADORA')
            ->update(['tipo' => 'CANCELAMENTO_QUALICORP']);

        DB::table('pos_venda_demanda_templates')
            ->where('tipo', 'CANCELAMENTO_INTERMEDIADORA')
            ->update(['tipo' => 'CANCELAMENTO_QUALICORP']);
        DB::table('pos_venda_demanda_templates')
            ->where('titulo', 'Cancelar vínculo com intermediadora')
            ->update(['titulo' => 'Cancelar plano Qualicorp']);
    }
};
