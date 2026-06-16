<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-mails retornados pela Assertiva, ligados a uma pessoa OU empresa.
 * `email_normalizado` (lowercase) indexado para o cache-first cruzado por e-mail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('people_db')->create('assertiva_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assertiva_pessoa_id')->nullable()->index();
            $table->unsignedBigInteger('assertiva_empresa_id')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('email_normalizado')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('people_db')->dropIfExists('assertiva_emails');
    }
};
