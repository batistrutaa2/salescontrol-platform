<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contas_pagamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('banco')->nullable();       // Itaú, Bradesco, etc.
            $table->string('agencia', 20)->nullable();
            $table->string('conta', 30)->nullable();
            $table->string('digito', 5)->nullable();
            $table->string('chave_pix')->nullable();    // e-mail, CPF, celular, EVP
            $table->string('descricao')->nullable();    // "Conta principal", etc.

            $table->boolean('is_default')->default(false)->index(); // conta padrão do usuário
            $table->timestamps();
        });

        // (Opcional) índice para buscas rápidas de conta padrão por usuário
        Schema::table('contas_pagamento', function (Blueprint $table) {
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagamento');
    }
};
