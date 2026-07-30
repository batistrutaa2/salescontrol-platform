<?php

use App\Enums\NaturezaEtapaSolicitacao;
use App\Enums\TipoSolicitacaoPosVenda;
use App\Models\PosVendaFluxoEtapa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * O fluxo de solicitações do vendedor (/comercial/minhas-demandas) passou a
 * gravar direto na Central de Solicitações do Pós-Venda. Este backfill copia as
 * solicitações antigas (demandas origem VENDEDOR) para a nova tabela, mapeando
 * tipo e status. As linhas originais ficam intactas como histórico.
 */
return new class extends Migration
{
    public function up(): void
    {
        $demandas = DB::table('demandas')
            ->where('origem', 'VENDEDOR')
            ->whereNotNull('venda_id')
            ->orderBy('id')
            ->get();

        if ($demandas->isEmpty()) {
            return;
        }

        foreach ($demandas->groupBy('empresa_id') as $empresaId => $doGrupo) {
            PosVendaFluxoEtapa::seedDefaults((int) $empresaId);

            $etapas = PosVendaFluxoEtapa::where('empresa_id', $empresaId)
                ->orderBy('ordem')->orderBy('id')
                ->get()
                ->groupBy('tipo');

            foreach ($doGrupo as $d) {
                $tipo = TipoSolicitacaoPosVenda::deTipoDemanda($d->tipo);
                $fluxo = $etapas->get($tipo->value, collect());

                $etapa = match ($d->status) {
                    'CONCLUIDA' => $fluxo->firstWhere('natureza', NaturezaEtapaSolicitacao::CONCLUIDA->value),
                    'CANCELADA' => $fluxo->firstWhere('natureza', NaturezaEtapaSolicitacao::CANCELADA->value)
                        ?? $fluxo->firstWhere('natureza', NaturezaEtapaSolicitacao::CONCLUIDA->value),
                    default => $fluxo->firstWhere('natureza', NaturezaEtapaSolicitacao::EM_ANDAMENTO->value),
                };

                if (! $etapa) {
                    continue;
                }

                $status = in_array($d->status, ['CONCLUIDA', 'CANCELADA'], true) ? $d->status : 'ABERTA';

                $solicitacaoId = DB::table('pos_venda_solicitacoes')->insertGetId([
                    'venda_id' => $d->venda_id,
                    'empresa_id' => $d->empresa_id,
                    'tipo' => $tipo->value,
                    'etapa_id' => $etapa->id,
                    'titulo' => $d->titulo,
                    'descricao' => $d->descricao,
                    'status' => $status,
                    'data_limite' => $d->data_limite,
                    'origem' => 'VENDEDOR',
                    'responsavel_id' => $d->assigned_to,
                    'created_by' => $d->created_by,
                    'concluida_em' => $d->concluida_em,
                    'created_at' => $d->created_at,
                    'updated_at' => $d->updated_at,
                ]);

                DB::table('pos_venda_solicitacao_historico')->insert([
                    'solicitacao_id' => $solicitacaoId,
                    'user_id' => $d->created_by,
                    'campo_alterado' => 'abertura',
                    'valor_anterior' => null,
                    'valor_novo' => $etapa->nome,
                    'observacao' => 'Solicitação migrada do fluxo antigo de demandas do vendedor.',
                    'created_at' => $d->created_at,
                    'updated_at' => $d->created_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Backfill de dados: sem rollback automático.
    }
};
