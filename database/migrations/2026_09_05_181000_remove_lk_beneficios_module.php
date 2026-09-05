<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'lk_beneficios_lead_comentarios',
            'lk_beneficios_lead_historico',
            'lk_beneficios_apolices',
            'lk_beneficios_parcelas',
            'lk_beneficios_movimentacoes',
            'lk_beneficios_beneficiarios',
            'lk_beneficios_contratos_detalhes_patrimonial',
            'lk_beneficios_contratos_detalhes_previdencia',
            'lk_beneficios_contratos_detalhes_vida',
            'lk_beneficios_contratos',
            'lk_beneficios_leads',
            'lk_beneficios_produtos',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasColumn('empresas', 'beneficios_reajuste_janela_dias')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->dropColumn('beneficios_reajuste_janela_dias');
            });
        }

        if (Schema::hasTable('users') && Schema::hasTable('user_roles')) {
            DB::table('users')
                ->where('user_role_id', 6)
                ->update([
                    'user_role_id' => 1,
                    'ativo' => 'N',
                    'updated_at' => now(),
                ]);

            DB::table('user_roles')->where('id', 6)->delete();
        }
    }

    public function down(): void
    {
        // O módulo foi removido definitivamente. Seus dados não são recriados no rollback.
    }
};
