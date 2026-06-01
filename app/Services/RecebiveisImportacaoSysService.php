<?php

namespace App\Services;

use App\Models\Operadora;
use App\Models\Plano;
use App\Models\Recebivel;
use App\Models\RegrasComissionamento;
use App\Models\Vendas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecebiveisImportacaoSysService
{
    private Carbon $dataCorte;

    private bool $dryRun;

    private int $vendasProcessadas = 0;

    private int $vendasSemRegra = 0;

    private int $vendasSemDataImplantacao = 0;

    private int $recebiveisNormais = 0;

    private int $recebiveisVitalicios = 0;

    private int $recebivelPagos = 0;

    private int $recebivelPendentes = 0;

    private array $warnings = [];

    public function __construct(Carbon $dataCorte, bool $dryRun = false)
    {
        $this->dataCorte = $dataCorte;
        $this->dryRun = $dryRun;
    }

    public function executar(): void
    {
        $vendas = Vendas::where('layout_venda', 'IMPORTACAO_SYS')
            ->whereDoesntHave('recebiveis')
            ->get();

        foreach ($vendas as $venda) {
            $this->processarVenda($venda);
        }
    }

    private function processarVenda(Vendas $venda): void
    {
        // Guard: venda precisa ter data de implantacao
        if (! $venda->data_implantacao) {
            $this->vendasSemDataImplantacao++;
            $this->warnings[] = "Venda #{$venda->id} ({$venda->numero_proposta}): Sem data de implantação";
            Log::warning("[RecebiveisImportacaoSys] Venda {$venda->id} sem data_implantacao. Ignorando.");

            return;
        }

        // Buscar operadora pelo nome salvo na venda
        $operadora = Operadora::where('nome', $venda->operadora)->first();
        if (! $operadora) {
            $this->vendasSemRegra++;
            $this->warnings[] = "Venda #{$venda->id} ({$venda->numero_proposta}): Operadora \"{$venda->operadora}\" não encontrada";
            Log::warning("[RecebiveisImportacaoSys] Operadora não encontrada para venda {$venda->id}: {$venda->operadora}");

            return;
        }

        // Determinar categoria baseado no tipo_contrato da venda
        $categoria = $venda->tipo_contrato === 'PME' ? 'PME' : 'ADESAO';

        // Buscar regra de comissionamento filtrando por categoria
        $regra = RegrasComissionamento::with('parcelas')
            ->where('empresa_id', $venda->empresa_id)
            ->where('operadora_id', $operadora->id)
            ->where('categoria', $categoria)
            ->first();

        if (! $regra) {
            $this->vendasSemRegra++;
            $this->warnings[] = "Venda #{$venda->id} ({$venda->numero_proposta}): Operadora \"{$venda->operadora}\" sem regra de comissionamento para categoria {$categoria}";
            Log::warning("[RecebiveisImportacaoSys] Sem regra de comissionamento para venda {$venda->id} (empresa {$venda->empresa_id}, operadora {$operadora->id}, categoria {$categoria})");

            return;
        }

        $parcelas = $regra->parcelas()->orderBy('parcela')->get();

        if ($parcelas->isEmpty()) {
            $this->vendasSemRegra++;
            $this->warnings[] = "Venda #{$venda->id} ({$venda->numero_proposta}): Regra sem parcelas definidas";
            Log::warning("[RecebiveisImportacaoSys] Regra {$regra->id} sem parcelas definidas. Venda {$venda->id} ignorada.");

            return;
        }

        // Resolver nome do plano
        $planoNome = $this->resolverNomePlano($venda);

        if ($this->dryRun) {
            $this->simularGeracaoRecebiveis($venda, $regra, $parcelas, $operadora, $planoNome);
        } else {
            $this->gerarRecebiveis($venda, $regra, $parcelas, $operadora, $planoNome);
        }

        $this->vendasProcessadas++;
    }

    private function resolverNomePlano(Vendas $venda): string
    {
        if (! empty($venda->plano_id)) {
            $plano = Plano::find($venda->plano_id);

            return $plano?->nome ?? $venda->nome_plano ?? 'N/A';
        }

        return $venda->nome_plano ?? 'N/A';
    }

    private function simularGeracaoRecebiveis(
        Vendas $venda,
        RegrasComissionamento $regra,
        $parcelas,
        Operadora $operadora,
        string $planoNome
    ): void {
        $dataImplantacao = Carbon::parse($venda->data_implantacao);

        // Simular parcelas normais
        foreach ($parcelas as $parcela) {
            $dataPrevista = $dataImplantacao->copy()->addMonths($parcela->parcela - 1);
            $status = $dataPrevista->lte($this->dataCorte) ? 'PAGO' : 'PENDENTE';

            $this->recebiveisNormais++;
            if ($status === 'PAGO') {
                $this->recebivelPagos++;
            } else {
                $this->recebivelPendentes++;
            }
        }

        // Simular parcelas vitalícias
        if ($regra->vitalicio && $regra->percentual_vitalicio > 0) {
            $ultimaParcelaNormal = $parcelas->max('parcela');
            $proximaParcela = $ultimaParcelaNormal + 1;
            $dataPrevista = $dataImplantacao->copy()->addMonths($proximaParcela - 1);

            while ($dataPrevista->lte($this->dataCorte)) {
                $this->recebiveisVitalicios++;
                $this->recebivelPagos++;

                $proximaParcela++;
                $dataPrevista = $dataImplantacao->copy()->addMonths($proximaParcela - 1);
            }

            // Uma parcela vitalícia após a data de corte (PENDENTE)
            $this->recebiveisVitalicios++;
            $this->recebivelPendentes++;
        }
    }

    private function gerarRecebiveis(
        Vendas $venda,
        RegrasComissionamento $regra,
        $parcelas,
        Operadora $operadora,
        string $planoNome
    ): void {
        $dataImplantacao = Carbon::parse($venda->data_implantacao);

        DB::beginTransaction();
        try {
            // Checar novamente dentro da transaction (race condition)
            $existeDentroTx = Recebivel::where('venda_id', $venda->id)->lockForUpdate()->count();
            if ($existeDentroTx > 0) {
                DB::rollBack();
                Log::info("[RecebiveisImportacaoSys] Venda {$venda->id} já possui recebíveis. Ignorando.");

                return;
            }

            // Gerar parcelas normais
            foreach ($parcelas as $parcela) {
                $valorParcela = ($parcela->percentual / 100) * $venda->valor_contrato;
                $dataPrevista = $dataImplantacao->copy()->addMonths($parcela->parcela - 1);
                $status = $dataPrevista->lte($this->dataCorte) ? 'PAGO' : 'PENDENTE';

                Recebivel::create([
                    'empresa_id' => $venda->empresa_id,
                    'venda_id' => $venda->id,
                    'vendedor_id' => $venda->user_id,
                    'operadora' => $operadora->nome,
                    'plano' => $planoNome,
                    'parcela' => $parcela->parcela,
                    'vitalicio' => false,
                    'valor' => $valorParcela,
                    'data_prevista' => $dataPrevista,
                    'data_recebimento' => $status === 'PAGO' ? $dataPrevista : null,
                    'status' => $status,
                ]);

                $this->recebiveisNormais++;
                if ($status === 'PAGO') {
                    $this->recebivelPagos++;
                } else {
                    $this->recebivelPendentes++;
                }
            }

            // Gerar parcelas vitalícias se aplicável
            if ($regra->vitalicio && $regra->percentual_vitalicio > 0) {
                $this->gerarRecebiveisVitalicios($venda, $regra, $operadora, $planoNome, $parcelas);
            }

            DB::commit();
            Log::info("[RecebiveisImportacaoSys] Venda {$venda->id}: recebíveis gerados com sucesso.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[RecebiveisImportacaoSys] Erro ao gerar recebíveis para venda {$venda->id}: {$e->getMessage()}");
            throw $e;
        }
    }

    private function gerarRecebiveisVitalicios(
        Vendas $venda,
        RegrasComissionamento $regra,
        Operadora $operadora,
        string $planoNome,
        $parcelasNormais
    ): void {
        $dataImplantacao = Carbon::parse($venda->data_implantacao);
        $ultimaParcelaNormal = $parcelasNormais->max('parcela');
        $valorVitalicio = ($regra->percentual_vitalicio / 100) * $venda->valor_contrato;

        $proximaParcela = $ultimaParcelaNormal + 1;
        $dataPrevista = $dataImplantacao->copy()->addMonths($proximaParcela - 1);

        // Gerar parcelas vitalícias pagas (até a data de corte)
        while ($dataPrevista->lte($this->dataCorte)) {
            Recebivel::create([
                'empresa_id' => $venda->empresa_id,
                'venda_id' => $venda->id,
                'vendedor_id' => $venda->user_id,
                'operadora' => $operadora->nome,
                'plano' => $planoNome,
                'parcela' => $proximaParcela,
                'vitalicio' => true,
                'valor' => $valorVitalicio,
                'data_prevista' => $dataPrevista,
                'data_recebimento' => $dataPrevista,
                'status' => 'PAGO',
            ]);

            $this->recebiveisVitalicios++;
            $this->recebivelPagos++;

            $proximaParcela++;
            $dataPrevista = $dataImplantacao->copy()->addMonths($proximaParcela - 1);
        }

        // Gerar uma parcela vitalícia pendente (após a data de corte)
        Recebivel::create([
            'empresa_id' => $venda->empresa_id,
            'venda_id' => $venda->id,
            'vendedor_id' => $venda->user_id,
            'operadora' => $operadora->nome,
            'plano' => $planoNome,
            'parcela' => $proximaParcela,
            'vitalicio' => true,
            'valor' => $valorVitalicio,
            'data_prevista' => $dataPrevista,
            'data_recebimento' => null,
            'status' => 'PENDENTE',
        ]);

        $this->recebiveisVitalicios++;
        $this->recebivelPendentes++;
    }

    public function getResumo(): array
    {
        return [
            'vendas_processadas' => $this->vendasProcessadas,
            'vendas_sem_regra' => $this->vendasSemRegra,
            'vendas_sem_data' => $this->vendasSemDataImplantacao,
            'recebiveis_normais' => $this->recebiveisNormais,
            'recebiveis_vitalicios' => $this->recebiveisVitalicios,
            'recebiveis_total' => $this->recebiveisNormais + $this->recebiveisVitalicios,
            'recebiveis_pagos' => $this->recebivelPagos,
            'recebiveis_pendentes' => $this->recebivelPendentes,
            'warnings' => $this->warnings,
        ];
    }
}
