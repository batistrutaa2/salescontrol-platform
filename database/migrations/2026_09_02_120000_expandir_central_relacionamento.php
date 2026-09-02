<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renovacao_oportunidades', function (Blueprint $table) {
            $table->string('telefone_preferencial', 30)->nullable()->after('contato_id');
            $table->string('email_preferencial')->nullable()->after('telefone_preferencial');
            $table->string('fonte_contato', 20)->nullable()->after('email_preferencial');
            $table->json('contatos_enriquecidos')->nullable()->after('fonte_contato');
            $table->timestamp('pesquisado_em')->nullable()->after('contatos_enriquecidos');
            $table->unsignedBigInteger('pesquisado_por_id')->nullable()->after('pesquisado_em');
            $table->string('situacao_plano', 32)->nullable()->after('pesquisado_por_id');
            $table->string('operadora_atual')->nullable()->after('situacao_plano');
            $table->string('plano_atual')->nullable()->after('operadora_atual');
            $table->decimal('valor_atual', 10, 2)->nullable()->after('plano_atual');
            $table->unsignedSmallInteger('vidas_atuais')->nullable()->after('valor_atual');
            $table->text('pendencia_pos_venda')->nullable()->after('vidas_atuais');
            $table->string('saude_status', 24)->default('NAO_ABORDADO')->after('pendencia_pos_venda');
            $table->string('vida_status', 24)->default('NAO_ABORDADO')->after('saude_status');
            $table->timestamp('ultima_interacao_em')->nullable()->after('vida_status');

            $table->index(['empresa_id', 'saude_status'], 'renovacao_saude_idx');
            $table->index(['empresa_id', 'vida_status'], 'renovacao_vida_idx');
            $table->index(['empresa_id', 'ultima_interacao_em'], 'renovacao_atividade_idx');
        });
    }

    public function down(): void
    {
        Schema::table('renovacao_oportunidades', function (Blueprint $table) {
            $table->dropIndex('renovacao_saude_idx');
            $table->dropIndex('renovacao_vida_idx');
            $table->dropIndex('renovacao_atividade_idx');
            $table->dropColumn([
                'telefone_preferencial', 'email_preferencial', 'fonte_contato', 'contatos_enriquecidos', 'pesquisado_em',
                'pesquisado_por_id', 'situacao_plano', 'operadora_atual', 'plano_atual',
                'valor_atual', 'vidas_atuais', 'pendencia_pos_venda', 'saude_status',
                'vida_status', 'ultima_interacao_em',
            ]);
        });
    }
};
