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
        Schema::create('whatsapp_instancias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('user_id')->constrained('users');
            $table->string('instance_name', 100)->unique();
            $table->string('instance_id', 100)->nullable();
            $table->string('numero_conectado', 20)->nullable();
            $table->enum('status', ['CRIADA', 'QRCODE', 'CONECTADA', 'DESCONECTADA', 'ERRO'])->default('CRIADA');
            $table->string('webhook_token', 64);
            $table->timestamp('last_status_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_instancias');
    }
};
