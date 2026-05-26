<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log duravel de cada envio de lead para a preditiva (reciclagem).
     * Diferente de log_preditiva (que e limpo a cada ciclo), este registro NAO
     * e apagado — e a fonte de auditoria de "o que foi enviado".
     */
    public function up(): void
    {
        Schema::create('preditiva_envios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('contato_id');
            $table->timestamp('enviado_em')->useCurrent();
            $table->enum('origem', ['MANUAL', 'AUTOMATICO'])->default('MANUAL');
            $table->unsignedBigInteger('enviado_por')->nullable();
            // Situacao do lead no momento do envio: SEM_ATRIBUICAO | REMARKETING | DESCARTADO
            $table->string('situacao_origem', 30)->nullable();
            // Dias sem contato no momento do envio (snapshot)
            $table->unsignedInteger('dias_inativo')->nullable();
            $table->timestamps();

            $table->index('empresa_id');
            $table->index('contato_id');
            $table->index('enviado_em');

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('contato_id')->references('id')->on('contatos')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('enviado_por')->references('id')->on('users')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preditiva_envios');
    }
};
