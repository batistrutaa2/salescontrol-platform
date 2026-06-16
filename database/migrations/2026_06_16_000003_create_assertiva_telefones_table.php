<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telefones (fixos/móveis) retornados pela Assertiva, ligados a uma pessoa OU empresa.
 *
 * `numero_normalizado` guarda apenas dígitos e é indexado para o cache-first cruzado
 * por telefone (a Assertiva devolve o número mascarado em `numero`).
 * Sem FK constrained: cross-db / mesma estratégia das tabelas filhas do Lemit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('people_db')->create('assertiva_telefones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assertiva_pessoa_id')->nullable()->index();
            $table->unsignedBigInteger('assertiva_empresa_id')->nullable()->index();
            $table->string('numero_normalizado', 20)->nullable()->index();
            $table->string('numero', 30)->nullable();
            $table->string('tipo', 10)->nullable(); // FIXO | MOVEL
            $table->boolean('whatsapp')->default(false);
            $table->boolean('nao_perturbe')->default(false);
            $table->string('relacao', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('people_db')->dropIfExists('assertiva_telefones');
    }
};
