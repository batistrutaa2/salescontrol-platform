<?php

namespace App\Services;

use App\Models\PosVendaDemandaTemplate;
use App\Models\VendaDemanda;
use App\Models\Vendas;
use Illuminate\Support\Facades\DB;

/**
 * Gera o check-list de demandas de pós-venda quando um contrato é implantado.
 *
 * NÃO depende de Auth — recebe quem está criando explicitamente (criadoPor),
 * para funcionar tanto no fluxo HTTP quanto em jobs/commands.
 */
class PosVendaDemandaService
{
    /**
     * Cria as demandas de pós-venda da venda no momento da implantação.
     *
     * O backoffice escolhe quais demandas gerar no modal de implantação; os IDs
     * escolhidos chegam em $templateIds. Quando $templateIds é null (chamada sem
     * seleção explícita), cai no padrão: templates ativos marcados como
     * gerar_automatico. Quando é um array (mesmo vazio), gera só os escolhidos.
     *
     * Idempotente: se a venda já possui qualquer demanda, não faz nada — re-implantar
     * o mesmo contrato não duplica o check-list.
     *
     * @param  array<int|string>|null  $templateIds  IDs de pos_venda_demanda_templates escolhidos.
     * @return int Quantidade de demandas criadas.
     */
    public function gerarParaVenda(Vendas $venda, ?array $templateIds = null, ?int $criadoPor = null): int
    {
        // Garante o check-list padrão para empresas que ainda não configuraram templates.
        PosVendaDemandaTemplate::seedDefaults($venda->empresa_id);

        $query = PosVendaDemandaTemplate::where('empresa_id', $venda->empresa_id)
            ->where('ativo', true);

        if (is_array($templateIds)) {
            $ids = array_filter(array_map('intval', $templateIds));
            if (empty($ids)) {
                return 0; // Backoffice optou por não gerar nenhuma demanda.
            }
            $query->whereIn('id', $ids);
        } else {
            $query->where('gerar_automatico', true);
        }

        $templates = $query->orderBy('ordem')->get();

        // Idempotência por tipo: não recria tipos que a venda já tem (ex.: o
        // cancelamento sinalizado no cadastro), mas complementa os que faltam.
        $tiposExistentes = VendaDemanda::where('venda_id', $venda->id)->pluck('tipo')->all();
        $templates = $templates->reject(fn ($t) => in_array($t->tipo, $tiposExistentes, true));

        if ($templates->isEmpty()) {
            return 0;
        }

        $criadoPor = $criadoPor ?? $venda->backoffice_id ?? $venda->user_id;
        $agora = now();

        $rows = $templates->map(fn ($t) => [
            'venda_id' => $venda->id,
            'empresa_id' => $venda->empresa_id,
            'created_by' => $criadoPor,
            'tipo' => $t->tipo,
            'titulo' => $t->titulo,
            'descricao' => $t->descricao,
            'status' => 'PENDENTE',
            'created_at' => $agora,
            'updated_at' => $agora,
        ])->all();

        DB::table('venda_demandas')->insert($rows);

        return count($rows);
    }
}
