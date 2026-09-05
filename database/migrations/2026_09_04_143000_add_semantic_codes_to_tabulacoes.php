<?php

use App\Enums\TabulationCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tabulacoes', function (Blueprint $table) {
            $table->string('codigo', 80)->nullable()->after('empresa_id');
        });

        $aliases = [
            'PROSPECTADO A' => TabulationCode::PROSPECCAO,
            'PROSPECCAO' => TabulationCode::PROSPECCAO,
            'REUNIAO' => TabulationCode::REUNIAO,
            'NEGOCIACAO' => TabulationCode::NEGOCIACAO,
            'DOCUMENTO' => TabulationCode::DOCUMENTO,
            'NEGOCIO FECHADO' => TabulationCode::NEGOCIO_FECHADO,
            'NEGOCIO NAO FECHADO' => TabulationCode::NEGOCIO_NAO_FECHADO,
            'REMARKETING' => TabulationCode::REMARKETING,
            'FOLLOW UP' => TabulationCode::FOLLOW_UP,
            'VENDA' => TabulationCode::VENDA,
            'ESTORNO' => TabulationCode::ESTORNO,
            'IMPLANTADO' => TabulationCode::IMPLANTADO,
            'SEM CONTATO' => TabulationCode::SEM_CONTATO,
            'AGENDAMENTO' => TabulationCode::AGENDAMENTO,
            'NOVOS CLIENTES' => TabulationCode::NOVOS_CLIENTES,
            'DECLINADO' => TabulationCode::DECLINADO,
            'ANALISE DE DOCUMENTOS' => TabulationCode::ANALISE_DOCUMENTOS,
            'PENDENCIA' => TabulationCode::PENDENCIA,
            'CONTR GERADO AGUARDANDO ASSINATURA' => TabulationCode::CONTRATO_GERADO_AGUARDANDO_ASSINATURA,
            'CONTRATO GERADO AGUARDANDO ASSINATURA' => TabulationCode::CONTRATO_GERADO_AGUARDANDO_ASSINATURA,
            'REGULARIZADO' => TabulationCode::REGULARIZADO,
            'BOLETO DISPONIVEL' => TabulationCode::BOLETO_DISPONIVEL,
            'ANALISE OPERADORA' => TabulationCode::ANALISE_OPERADORA,
            'ANALISE DA OPERADORA' => TabulationCode::ANALISE_OPERADORA,
            'AGUARD ASSINATURA DA DS' => TabulationCode::AGUARDANDO_ASSINATURA_DS,
            'AGUARDANDO ASSINATURA DA DS' => TabulationCode::AGUARDANDO_ASSINATURA_DS,
        ];

        foreach (DB::table('tabulacoes')->whereNull('codigo')->get(['id', 'empresa_id', 'descricao']) as $tabulacao) {
            $normalized = preg_replace('/[^A-Z0-9]+/', ' ', Str::upper(Str::ascii((string) $tabulacao->descricao)));
            $normalized = trim((string) $normalized);
            $codigo = $aliases[$normalized] ?? null;

            if ($codigo && ! DB::table('tabulacoes')->where('empresa_id', $tabulacao->empresa_id)->where('codigo', $codigo)->exists()) {
                DB::table('tabulacoes')->where('id', $tabulacao->id)->update(['codigo' => $codigo]);
            }
        }

        foreach (DB::table('empresas')->pluck('id') as $empresaId) {
            foreach (TabulationCode::defaults() as $codigo => $definition) {
                if (! DB::table('tabulacoes')->where('empresa_id', $empresaId)->where('codigo', $codigo)->exists()) {
                    DB::table('tabulacoes')->insert($definition + [
                        'empresa_id' => $empresaId,
                        'codigo' => $codigo,
                        'sub_tabulacao' => 'N',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::table('tabulacoes', function (Blueprint $table) {
            $table->unique(['empresa_id', 'codigo'], 'tabulacoes_empresa_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tabulacoes', function (Blueprint $table) {
            $table->dropUnique('tabulacoes_empresa_codigo_unique');
            $table->dropColumn('codigo');
        });
    }
};
