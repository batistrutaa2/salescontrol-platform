<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelamentos_liminares_historico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cancelamento_liminar_id');
            $table->foreign('cancelamento_liminar_id', 'fk_canc_lim_hist_liminar_id')
                ->references('id')->on('cancelamentos_liminares')
                ->onUpdate('no action')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onUpdate('no action')->onDelete('no action');
            $table->string('campo_alterado');
            $table->string('valor_anterior')->nullable();
            $table->string('valor_novo');
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelamentos_liminares_historico');
    }
};
