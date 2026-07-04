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
        Schema::create('whatsapp_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('conversa_id')->constrained('whatsapp_conversas');
            $table->string('message_id', 100);
            $table->enum('direcao', ['IN', 'OUT']);
            $table->enum('tipo', ['text', 'image', 'video', 'audio', 'ptt', 'sticker', 'document', 'location', 'contact', 'unknown'])->default('text');
            $table->text('body')->nullable();
            $table->string('media_path', 500)->nullable();
            $table->string('media_mime', 100)->nullable();
            $table->unsignedInteger('media_size')->nullable();
            $table->string('quoted_message_id', 100)->nullable();
            $table->tinyInteger('ack')->default(0);
            $table->enum('status_envio', ['PENDENTE', 'ENVIADA', 'ERRO'])->nullable();
            $table->text('erro_envio')->nullable();
            $table->timestamp('message_timestamp');
            $table->timestamps();

            $table->unique(['conversa_id', 'message_id']);
            $table->index(['conversa_id', 'message_timestamp']);
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_mensagens');
    }
};
