<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recebiveis', function (Blueprint $table) {
            $table->boolean('vitalicio')
                ->default(false)
                ->after('parcela')
                ->comment('true = parcela vitalícia (além das parcelas fixas da regra)');
        });

        DB::transaction(function () {
            // Passo A — preserva o comportamento antigo para tudo (inclusive
            // recebíveis cuja operadora não tem regra de comissionamento).
            DB::statement('UPDATE recebiveis SET vitalicio = (parcela > 3)');

            // Passo B — corrige pelo limite real da regra: uma parcela é vitalícia
            // quando seu número é maior que a maior parcela definida na regra da
            // operadora (regras_comissionamento_parcelas). Casa operadora pelo nome
            // (recebiveis.operadora = operadoras.nome) e pela empresa.
            DB::statement('
                UPDATE recebiveis r
                JOIN operadoras o
                    ON o.nome = r.operadora
                JOIN regras_comissionamento rc
                    ON rc.operadora_id = o.id
                   AND rc.empresa_id = r.empresa_id
                JOIN (
                    SELECT regra_id, MAX(parcela) AS max_p
                    FROM regras_comissionamento_parcelas
                    GROUP BY regra_id
                ) mp ON mp.regra_id = rc.id
                SET r.vitalicio = (r.parcela > mp.max_p)
            ');
        });
    }

    public function down(): void
    {
        Schema::table('recebiveis', function (Blueprint $table) {
            $table->dropColumn('vitalicio');
        });
    }
};
