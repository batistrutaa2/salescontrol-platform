<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_conversas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('instancia_id')->constrained('whatsapp_instancias');
            $table->foreignId('user_id')->constrained('users');
            $table->string('remote_jid', 64);
            $table->string('numero', 20);
            $table->string('numero_normalizado', 10)->nullable();
            $table->string('nome_whatsapp', 255)->nullable();
            $table->string('foto_url', 500)->nullable();
            $table->foreignId('contato_id')->nullable()->constrained('contatos');
            $table->foreignId('tabulacao_id')->nullable()->constrained('tabulacoes');
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 255)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->enum('arquivada', ['Y', 'N'])->default('N');
            $table->timestamps();

            $table->unique(['instancia_id', 'remote_jid']);
            $table->index(['empresa_id', 'user_id', 'last_message_at']);
            $table->index(['empresa_id', 'tabulacao_id']);
            $table->index('numero_normalizado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversas');
    }
};
