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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GerarRecebiveisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $vendaId) {}

    public function handle(): void
    {
        Log::info("Job iniciado para venda {$this->vendaId}");

        $venda = Vendas::findOrFail($this->vendaId);

        // 🔎 Buscar operadora pelo nome salvo na venda
        $operadora = Operadora::where('nome', $venda->operadora)->first();

        if (!$operadora) {
            Log::warning("Operadora não encontrada: {$venda->operadora}");
            return;
        }

        // 🔎 Buscar regra de comissionamento
        $regra = RegrasComissionamento::with('parcelas')
            ->where('empresa_id', $venda->empresa_id)
            ->where('operadora_id', $operadora->id)
            ->first();

        if (!$regra) {
            Log::warning("Nenhuma regra encontrada para empresa {$venda->empresa_id} e operadora {$operadora->id}");
            return;
        }

        // 🔎 Resolver nome do plano
        $planoNome = 'N/A';
        if (!empty($venda->plano_id)) {
            $plano = Plano::find($venda->plano_id);
            $planoNome = $plano?->nome ?? $venda->nome_plano ?? 'N/A';
        } elseif (!empty($venda->nome_plano)) {
            $planoNome = $venda->nome_plano;
        }

        // 💰 Gerar recebíveis conforme parcelas da regra
        $parcelas = $regra->parcelas()->orderBy('parcela')->get();

        foreach ($parcelas as $parcela) {
            $valorParcela = ($parcela->percentual / 100) * $venda->valor_contrato;

            Recebivel::create([
                'empresa_id'    => $venda->empresa_id,
                'venda_id'      => $venda->id,
                'vendedor_id'   => $venda->user_id,
                'operadora'     => $operadora->nome,
                'plano'         => $planoNome,
                'parcela'       => $parcela->parcela,
                'valor'         => $valorParcela,
                'data_prevista' => Carbon::parse($venda->data_implantacao)
                                         ->addMonths($parcela->parcela - 1),
                'status'        => 'PENDENTE',
            ]);
        }

        // ℹ️ Parcelas vitalícias são geradas progressivamente ao pagar cada parcela
        // (ver Financeiro::gerarProximaParcelaVitalicia)
        if ($regra->vitalicio && $regra->percentual_vitalicio > 0) {
            Log::info("Venda {$venda->id} tem regra vitalícia ({$regra->percentual_vitalicio}%). Parcelas extras serão geradas ao pagar.");
        }

        Log::info("Job finalizado para venda {$venda->id}");
    }
}
