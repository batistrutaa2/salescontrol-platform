<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache de pessoas físicas enriquecidas pela Assertiva (API Localize V3).
 *
 * Mora no banco secundário `people_db`, ao lado das tabelas do Lemit (`pessoas`),
 * isolada por fonte para permitir confronto entre as duas bases. Sem `empresa_id`:
 * é um cache global deduplicado por CPF (mesmo precedente do Lemit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('people_db')->create('assertiva_pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('cpf', 11)->unique();
            $table->string('nome')->nullable();
            $table->string('sexo', 20)->nullable();
            $table->string('data_nascimento', 20)->nullable();
            $table->string('mae_nome')->nullable();
            $table->string('mae_cpf', 11)->nullable();
            $table->string('situacao_cadastral', 40)->nullable();
            $table->string('protocolo')->nullable();
            $table->timestamp('data_consulta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('people_db')->dropIfExists('assertiva_pessoas');
    }
};
