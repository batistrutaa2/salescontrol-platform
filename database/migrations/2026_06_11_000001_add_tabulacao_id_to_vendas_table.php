<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status próprio do contrato (venda), desacoplado do status do contato.
     * Um contato pode ter várias vendas em estágios diferentes do ciclo de vida.
     */
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->unsignedBigInteger('tabulacao_id')->nullable()->after('contato_id');
            $table->timestamp('tabulacao_updated_at')->nullable()->after('tabulacao_id');

            $table->foreign('tabulacao_id')
                ->references('id')
                ->on('tabulacoes')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->index(['empresa_id', 'tabulacao_id']);
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropForeign(['tabulacao_id']);
            $table->dropIndex(['empresa_id', 'tabulacao_id']);
            $table->dropColumn(['tabulacao_id', 'tabulacao_updated_at']);
        });
    }
};
