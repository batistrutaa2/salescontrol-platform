<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Endereços retornados pela Assertiva, ligados a uma pessoa OU empresa.
 * Banco secundário `people_db`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('people_db')->create('assertiva_enderecos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assertiva_pessoa_id')->nullable()->index();
            $table->unsignedBigInteger('assertiva_empresa_id')->nullable()->index();
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep', 9)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('people_db')->dropIfExists('assertiva_enderecos');
    }
};
