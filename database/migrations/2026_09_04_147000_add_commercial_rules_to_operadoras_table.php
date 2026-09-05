<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operadoras', function (Blueprint $table) {
            $table->string('coparticipacao_formato', 30)->default('SIM_NAO')->after('diretorio_documentos');
            $table->boolean('angariacao_padrao')->default(false)->after('coparticipacao_formato');
        });

        // Compatibilidade pontual: converte a regra histórica em configuração.
        // A aplicação não volta a inferir comportamento pelo nome da operadora.
        DB::table('operadoras')
            ->whereRaw('UPPER(nome) LIKE ?', ['%AMIL%'])
            ->update(['coparticipacao_formato' => 'PARCIAL_COMPLETA']);

        DB::table('operadoras')
            ->whereRaw('UPPER(TRIM(nome)) = ?', ['AMIL - SUPERMED'])
            ->update(['angariacao_padrao' => true]);
    }

    public function down(): void
    {
        Schema::table('operadoras', function (Blueprint $table) {
            $table->dropColumn(['coparticipacao_formato', 'angariacao_padrao']);
        });
    }
};
