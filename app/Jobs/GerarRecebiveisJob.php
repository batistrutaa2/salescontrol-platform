<?php

namespace App\Jobs;

use App\Models\Vendas;
use App\Models\Recebivel;
use App\Models\RegrasComissionamento;
use App\Models\Operadora;
use App\Models\Plano;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GerarRecebiveisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $vendaId) {}

    public function handle(): void
    {
        $venda = Vendas::find($this->vendaId);

        if (!$venda) {
            Log::warning("[Recebiveis] Venda {$this->vendaId} nao encontrada. Ignorando.");
            return;
        }

        // Guard 1: venda precisa ter data de implantacao
        if (!$venda->data_implantacao) {
            Log::warning("[Recebiveis] Venda {$venda->id} sem data_implantacao. Ignorando.");
            return;
        }

        // Guard 2: se ja existe QUALQUER recebivel para esta venda, nao toca
        $existentes = Recebivel::where('venda_id', $venda->id)->count();
        if ($existentes > 0) {
            Log::info("[Recebiveis] Venda {$venda->id} ja possui {$existentes} recebivel(is). Ignorando.");
            return;
        }

        // Guard 3: buscar operadora pelo nome salvo na venda
        $operadora = Operadora::where('nome', $venda->operadora)->first();
        if (!$operadora) {
            Log::warning("[Recebiveis] Operadora nao encontrada para venda {$venda->id}: {$venda->operadora}");
            return;
        }

        // Guard 4: buscar regra de comissionamento
        $regra = RegrasComissionamento::with('parcelas')
            ->where('empresa_id', $venda->empresa_id)
            ->where('operadora_id', $operadora->id)
            ->first();

        if (!$regra) {
            Log::info("[Recebiveis] Sem regra de comissionamento para venda {$venda->id} (empresa {$venda->empresa_id}, operadora {$operadora->id}). Ignorando.");
            return;
        }

        $parcelas = $regra->parcelas()->orderBy('parcela')->get();

        if ($parcelas->isEmpty()) {
            Log::warning("[Recebiveis] Regra {$regra->id} sem parcelas definidas. Venda {$venda->id} ignorada.");
            return;
        }

        // Resolver nome do plano
        $planoNome = 'N/A';
        if (!empty($venda->plano_id)) {
            $plano = Plano::find($venda->plano_id);
            $planoNome = $plano?->nome ?? $venda->nome_plano ?? 'N/A';
        } elseif (!empty($venda->nome_plano)) {
            $planoNome = $venda->nome_plano;
        }

        // Gerar recebiveis dentro de transaction
        DB::beginTransaction();
        try {
            // Checar novamente dentro da transaction (race condition)
            $existeDentroTx = Recebivel::where('venda_id', $venda->id)->lockForUpdate()->count();
            if ($existeDentroTx > 0) {
                DB::rollBack();
                Log::info("[Recebiveis] Venda {$venda->id} ja possui recebiveis (verificacao em transaction). Ignorando.");
                return;
            }

            foreach ($parcelas as $parcela) {
                $valorParcela = ($parcela->percentual / 100) * $venda->valor_contrato;

                Recebivel::create([
                    'empresa_id'    => $venda->empresa_id,
                    'venda_id'      => $venda->id,
                    'vendedor_id'   => $venda->user_id,
                    'operadora'     => $operadora->nome,
                    'plano'         => $planoNome,
                    'parcela'       => $parcela->parcela,
                    'vitalicio'     => false,
                    'valor'         => $valorParcela,
                    'data_prevista' => Carbon::parse($venda->data_implantacao)
                                             ->addMonths($parcela->parcela - 1),
                    'status'        => 'PENDENTE',
                ]);
            }

            DB::commit();
            Log::info("[Recebiveis] Venda {$venda->id}: {$parcelas->count()} parcela(s) gerada(s) com sucesso.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[Recebiveis] Erro ao gerar recebiveis para venda {$venda->id}: {$e->getMessage()}");
            throw $e;
        }

        if ($regra->vitalicio && $regra->percentual_vitalicio > 0) {
            Log::info("[Recebiveis] Venda {$venda->id} tem regra vitalicia ({$regra->percentual_vitalicio}%). Parcelas extras serao geradas ao pagar.");
        }
    }
}
