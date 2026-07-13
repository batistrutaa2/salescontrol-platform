<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Expande o enum de parentesco para aceitar todas as opções do formulário
     * de nova proposta (irmão, neto, avô, etc.).
     */
    public function up(): void
    {
        // ALTER TABLE usando DB::statement porque o enum precisa ser recriado
        DB::statement("ALTER TABLE vendas_dependentes MODIFY parentesco ENUM('CONJUGE','COMPANHEIRO','FILHO','ENTEADO','PAI_MAE','SOGRO','IRMAO','NETO','AVO','BISNETO','BISAVO','TIO','SOBRINHO','PRIMO','GENRO_NORA','CUNHADO','TUTELADO','OUTROS') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Converte valores fora do enum original para OUTROS antes de reduzir o enum
        DB::statement("UPDATE vendas_dependentes SET parentesco = 'OUTROS' WHERE parentesco NOT IN ('CONJUGE','FILHO','PAI_MAE','SOBRINHO','OUTROS')");
        DB::statement("ALTER TABLE vendas_dependentes MODIFY parentesco ENUM('CONJUGE','FILHO','PAI_MAE','SOBRINHO','OUTROS') NULL");
    }
};
