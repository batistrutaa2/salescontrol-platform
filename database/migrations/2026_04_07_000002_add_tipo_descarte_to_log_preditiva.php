<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_preditiva', function (Blueprint $table) {
            // nullable = compatibilidade com registros legados (tratados como SOFT)
            $table->enum('tipo_descarte', ['SOFT', 'HARD'])->nullable()->after('acao');

            // Índice para acelerar a contagem de descartes por tipo na query principal
            $table->index(
                ['empresa_id', 'contato_id', 'acao', 'tipo_descarte'],
                'idx_log_preditiva_descartes'
            );

            // Índice para o filtro de cooldown (MAX created_at por contato)
            $table->index(
                ['contato_id', 'empresa_id', 'acao', 'created_at'],
                'idx_log_preditiva_cooldown'
            );
        });
    }

    public function down(): void
    {
        Schema::table('log_preditiva', function (Blueprint $table) {
            $table->dropIndex('idx_log_preditiva_descartes');
            $table->dropIndex('idx_log_preditiva_cooldown');
            $table->dropColumn('tipo_descarte');
        });
    }
};
