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
        Schema::table('transferencia_contatos', function (Blueprint $table) {
            // Primeiro, remover a foreign key constraint
            $table->dropForeign(['de_users_id']);

            // Alterar a coluna para aceitar NULL
            $table->bigInteger('de_users_id')->unsigned()->nullable()->change();

            // Recriar a foreign key constraint permitindo NULL
            $table->foreign('de_users_id')
                  ->references('id')
                  ->on('users')
                  ->onUpdate('NO ACTION')
                  ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transferencia_contatos', function (Blueprint $table) {
            // Remover a foreign key
            $table->dropForeign(['de_users_id']);

            // Voltar a coluna para NOT NULL
            $table->bigInteger('de_users_id')->unsigned()->nullable(false)->change();

            // Recriar a foreign key constraint original
            $table->foreign('de_users_id')
                  ->references('id')
                  ->on('users')
                  ->onUpdate('NO ACTION')
                  ->onDelete('NO ACTION');
        });
    }
};
