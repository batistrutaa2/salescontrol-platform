<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preditiva_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id')->unique();
            // Horas mínimas entre tentativas para o mesmo lead (0 = sem cooldown)
            $table->unsignedInteger('cooldown_horas')->default(0);
            // Dias antes de um lead descartado pelo usuário voltar a aparecer para ele
            // null = exclusão permanente (comportamento original)
            $table->unsignedInteger('exclusao_usuario_dias')->nullable()->default(null);
            // Máximo de descartes SOFT antes de remover o lead da fila
            $table->unsignedInteger('limite_descartes_soft')->default(5);
            // Máximo de descartes HARD — 1 já remove imediatamente
            $table->unsignedInteger('limite_descartes_hard')->default(1);
            $table->timestamps();

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preditiva_configuracoes');
    }
};
