<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adiciona campos para o novo layout PME nos titulares
     */
    public function up(): void
    {
        Schema::table('vendas_titulares', function (Blueprint $table) {
            // Segundo telefone
            $table->string('telefone2', 50)->nullable()->after('telefone');

            // Cargo do titular na empresa
            $table->enum('cargo', ['SOCIO', 'DIRETOR', 'GERENTE', 'FUNCIONARIO', 'PRESTADOR', 'ESTAGIARIO'])->nullable()->after('telefone2');

            // CPF do titular (útil para identificação)
            $table->string('cpf', 14)->nullable()->after('nome');

            // Data de nascimento
            $table->date('data_nascimento')->nullable()->after('cpf');

            // Informações de plano anterior (portabilidade)
            $table->enum('plano_anterior', ['SIM', 'NAO'])->default('NAO')->after('coparticipacao');
            $table->unsignedBigInteger('operadora_anterior_id')->nullable()->after('plano_anterior');

            // Foreign key para operadora anterior
            $table->foreign('operadora_anterior_id')
                ->references('id')
                ->on('operadoras')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas_titulares', function (Blueprint $table) {
            $table->dropForeign(['operadora_anterior_id']);
            $table->dropColumn([
                'telefone2',
                'cargo',
                'cpf',
                'data_nascimento',
                'plano_anterior',
                'operadora_anterior_id',
            ]);
        });
    }
};
