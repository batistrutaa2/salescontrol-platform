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
        Schema::table('comercial_reunioes', function (Blueprint $table) {
            // Adicionar coluna empresa_id após a coluna id
            $table->unsignedBigInteger('empresa_id')->after('id');

            // Adicionar chave estrangeira
            $table->foreign('empresa_id')->references('id')->on('empresas');

            // Adicionar índice para melhorar performance de consultas
            $table->index('empresa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comercial_reunioes', function (Blueprint $table) {
            // Remover chave estrangeira e coluna
            $table->dropForeign(['empresa_id']);
            $table->dropIndex(['empresa_id']);
            $table->dropColumn('empresa_id');
        });
    }
};
