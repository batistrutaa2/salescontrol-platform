<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            // Origem da demanda: INTERNA (tarefa do setor) ou VENDEDOR (solicitação de pós-venda).
            $table->enum('origem', ['INTERNA', 'VENDEDOR'])->default('INTERNA')->after('empresa_id')->index();
            // Contrato implantado ao qual a solicitação do vendedor se refere (null para internas).
            $table->foreignId('venda_id')->nullable()->after('origem')->constrained('vendas')->onDelete('set null');
            // Tipo da demanda de pós-venda (valor de App\Enums\TipoDemandaContrato); null para internas.
            $table->string('tipo')->nullable()->after('venda_id');
        });
    }

    public function down(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dropForeign(['venda_id']);
            $table->dropColumn(['origem', 'venda_id', 'tipo']);
        });
    }
};
