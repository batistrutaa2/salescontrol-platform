<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove os módulos Academia Comercial (escola) e Estudo, retirados do produto em
 * 22/10/2026. Em produção as tabelas estavam vazias nessa data.
 *
 * Irreversível: o down() não recria as tabelas. Para voltar, restaurar o backup
 * do banco e reverter o commit que apagou o código dos módulos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Filhas antes das mães, por causa das chaves estrangeiras.
        Schema::dropIfExists('escola_aula_progresso');
        Schema::dropIfExists('escola_aula_materiais');
        Schema::dropIfExists('escola_aulas');
        Schema::dropIfExists('escola_modulos');

        Schema::dropIfExists('estudo_vidas');
        Schema::dropIfExists('estudo_itens');
        Schema::dropIfExists('estudos');

        if (Schema::hasColumn('users', 'escola_habilitada')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('escola_habilitada');
            });
        }

        if (Schema::hasColumn('empresas', 'escola_percentual_conclusao')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->dropColumn('escola_percentual_conclusao');
            });
        }
    }

    public function down(): void
    {
        // Sem volta automática: ver o comentário da classe.
    }
};
