<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adiciona campos faltantes para o layout PME nos titulares
     */
    public function up(): void
    {
        Schema::table('vendas_titulares', function (Blueprint $table) {
            // Verificar e adicionar plano_anterior se não existir
            if (!Schema::hasColumn('vendas_titulares', 'plano_anterior')) {
                $table->enum('plano_anterior', ['SIM', 'NAO'])->default('NAO')->after('coparticipacao');
            }

            // Verificar e adicionar operadora_anterior_id se não existir
            if (!Schema::hasColumn('vendas_titulares', 'operadora_anterior_id')) {
                $table->unsignedBigInteger('operadora_anterior_id')->nullable()->after('plano_anterior');

                $table->foreign('operadora_anterior_id')
                    ->references('id')
                    ->on('operadoras')
                    ->onUpdate('no action')
                    ->onDelete('no action');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas_titulares', function (Blueprint $table) {
            if (Schema::hasColumn('vendas_titulares', 'operadora_anterior_id')) {
                $table->dropForeign(['operadora_anterior_id']);
                $table->dropColumn('operadora_anterior_id');
            }

            if (Schema::hasColumn('vendas_titulares', 'plano_anterior')) {
                $table->dropColumn('plano_anterior');
            }
        });
    }
};
