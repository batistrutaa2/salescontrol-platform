<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A venda nunca é atrelada de fato ao cancelamento via liminar — o processo
     * passa a nascer só com os dados da procuração. Tornamos venda_id (com FK),
     * beneficiário e nome_contrato opcionais.
     */
    public function up(): void
    {
        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->dropForeign(['venda_id']);
        });

        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->unsignedBigInteger('venda_id')->nullable()->change();
            $table->string('nome_contrato')->nullable()->change();
            $table->enum('beneficiario_tipo', ['TITULAR', 'DEPENDENTE'])->nullable()->change();
            $table->unsignedBigInteger('beneficiario_id')->nullable()->change();
        });

        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->foreign('venda_id')->references('id')->on('vendas')
                ->onUpdate('no action')->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->dropForeign(['venda_id']);
        });

        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->unsignedBigInteger('venda_id')->nullable(false)->change();
            $table->string('nome_contrato')->nullable(false)->change();
            $table->enum('beneficiario_tipo', ['TITULAR', 'DEPENDENTE'])->nullable(false)->change();
            $table->unsignedBigInteger('beneficiario_id')->nullable(false)->change();
        });

        Schema::table('cancelamentos_liminares', function (Blueprint $table) {
            $table->foreign('venda_id')->references('id')->on('vendas')
                ->onUpdate('no action')->onDelete('no action');
        });
    }
};
