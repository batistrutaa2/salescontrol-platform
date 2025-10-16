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
        Schema::table('lancamentos_debito_credito', function (Blueprint $table) {
            // Parcelamento
            $table->boolean('parcelado')->default(false)->after('descricao');
            $table->unsignedInteger('parcela_atual')->nullable()->after('parcelado');
            $table->unsignedInteger('parcelas_total')->nullable()->after('parcela_atual');
            $table->decimal('valor_total_original', 15, 2)->nullable()->after('parcelas_total');
            $table->unsignedBigInteger('lancamento_principal_id')->nullable()->after('valor_total_original');

            // Índice para buscar parcelas relacionadas
            $table->index('lancamento_principal_id');

            // FK para relacionar parcelas
            $table->foreign('lancamento_principal_id')
                  ->references('id')
                  ->on('lancamentos_debito_credito')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lancamentos_debito_credito', function (Blueprint $table) {
            $table->dropForeign(['lancamento_principal_id']);
            $table->dropIndex(['lancamento_principal_id']);
            $table->dropColumn([
                'parcelado',
                'parcela_atual',
                'parcelas_total',
                'valor_total_original',
                'lancamento_principal_id'
            ]);
        });
    }
};
