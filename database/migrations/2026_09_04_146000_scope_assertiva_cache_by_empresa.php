<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('people_db')->table('assertiva_pessoas', function (Blueprint $table) {
            $table->dropUnique(['cpf']);
            $table->unsignedBigInteger('empresa_id')->nullable()->after('id')->index();
            $table->unique(['empresa_id', 'cpf'], 'assertiva_pessoas_empresa_cpf_unique');
        });

        Schema::connection('people_db')->table('assertiva_empresas', function (Blueprint $table) {
            $table->dropUnique(['cnpj']);
            $table->unsignedBigInteger('empresa_id')->nullable()->after('id')->index();
            $table->unique(['empresa_id', 'cnpj'], 'assertiva_empresas_empresa_cnpj_unique');
        });

        Schema::connection('people_db')->table('assertiva_telefones', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')->nullable()->after('id')->index();
            $table->index(['empresa_id', 'numero_normalizado'], 'assertiva_telefones_empresa_numero_index');
        });

        Schema::connection('people_db')->table('assertiva_enderecos', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')->nullable()->after('id')->index();
        });

        Schema::connection('people_db')->table('assertiva_emails', function (Blueprint $table) {
            $table->unsignedBigInteger('empresa_id')->nullable()->after('id')->index();
            $table->index(['empresa_id', 'email_normalizado'], 'assertiva_emails_empresa_email_index');
        });
    }

    public function down(): void
    {
        Schema::connection('people_db')->table('assertiva_emails', function (Blueprint $table) {
            $table->dropIndex('assertiva_emails_empresa_email_index');
            $table->dropColumn('empresa_id');
        });

        Schema::connection('people_db')->table('assertiva_enderecos', function (Blueprint $table) {
            $table->dropColumn('empresa_id');
        });

        Schema::connection('people_db')->table('assertiva_telefones', function (Blueprint $table) {
            $table->dropIndex('assertiva_telefones_empresa_numero_index');
            $table->dropColumn('empresa_id');
        });

        Schema::connection('people_db')->table('assertiva_empresas', function (Blueprint $table) {
            $table->dropUnique('assertiva_empresas_empresa_cnpj_unique');
            $table->dropColumn('empresa_id');
            $table->unique('cnpj');
        });

        Schema::connection('people_db')->table('assertiva_pessoas', function (Blueprint $table) {
            $table->dropUnique('assertiva_pessoas_empresa_cpf_unique');
            $table->dropColumn('empresa_id');
            $table->unique('cpf');
        });
    }
};
