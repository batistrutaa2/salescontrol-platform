<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda o objeto `resposta` completo da Assertiva (JSON) para que nenhum campo
 * do retorno se perca — o front renderiza a partir daqui. As colunas/tabelas
 * indexadas (cpf/cnpj/telefone/email) continuam servindo ao cache-first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('people_db')->table('assertiva_pessoas', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('protocolo');
        });

        Schema::connection('people_db')->table('assertiva_empresas', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('protocolo');
        });
    }

    public function down(): void
    {
        Schema::connection('people_db')->table('assertiva_pessoas', function (Blueprint $table) {
            $table->dropColumn('payload');
        });

        Schema::connection('people_db')->table('assertiva_empresas', function (Blueprint $table) {
            $table->dropColumn('payload');
        });
    }
};
