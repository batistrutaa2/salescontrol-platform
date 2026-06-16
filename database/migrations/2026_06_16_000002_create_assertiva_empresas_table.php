<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache de pessoas jurídicas enriquecidas pela Assertiva (API Localize V3).
 * Banco secundário `people_db`. Deduplicado por CNPJ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('people_db')->create('assertiva_empresas', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 14)->unique();
            $table->string('razao_social')->nullable();
            $table->string('nome_fantasia')->nullable();
            $table->string('data_abertura', 20)->nullable();
            $table->string('cnae', 20)->nullable();
            $table->string('cnae_descricao')->nullable();
            $table->string('situacao_cadastral', 40)->nullable();
            $table->string('protocolo')->nullable();
            $table->timestamp('data_consulta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('people_db')->dropIfExists('assertiva_empresas');
    }
};
