<?php

namespace App\Services;

use App\Enums\Tabulations;
use App\Models\Vendas;

class StatusContratoWhatsappFormatter
{
    public function format(
        Vendas $venda,
        int $tabulacaoId,
        string $tabulacaoDescricao,
        ?string $alteradoPorNome,
        ?string $motivo,
        ?string $nomeEmpresa = null,
    ): string {
        $dados = [
            'nome_contrato' => $venda->nome_contrato ?: ('Contrato #'.$venda->id),
            'numero_proposta' => $venda->numero_proposta ?: 'N/I',
            'operadora' => $venda->operadora ?: 'N/I',
            'nome_plano' => $venda->nome_plano ?: 'N/I',
            'valor_contrato' => $this->moeda($venda->valor_contrato),
            'motivo' => trim((string) ($motivo ?? '')),
            'alterado_por' => $alteradoPorNome ?: 'Sistema',
            'descricao_status' => $tabulacaoDescricao,
            'nome_empresa' => $nomeEmpresa ?: 'SalesControl',
        ];

        return match ($tabulacaoId) {
            Tabulations::IMPLANTADO => $this->templateImplantado($dados),
            Tabulations::BOLETO_DISPONIVEL => $this->templateBoletoDisponivel($dados),
            Tabulations::PENDENCIA => $this->templatePendencia($dados),
            Tabulations::ESTORNO => $this->templateEstorno($dados),
            Tabulations::REGULARIZADO => $this->templateRegularizado($dados),
            Tabulations::ANALISE_OPERADORA => $this->templateAnaliseOperadora($dados),
            default => $this->templateGenerico($dados),
        };
    }

    private function templateImplantado(array $d): string
    {
        return implode("\n", [
            '✅ *Contrato Implantado!*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            "📄 Contrato: *{$d['nome_contrato']}*",
            "🔢 Proposta: {$d['numero_proposta']}",
            "🏥 Operadora: {$d['operadora']} — {$d['nome_plano']}",
            "💰 Valor: {$d['valor_contrato']}",
            '',
            'Parabéns! Sua venda foi implantada com sucesso.',
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            "_Alterado por: {$d['alterado_por']}_",
        ]);
    }

    private function templateBoletoDisponivel(array $d): string
    {
        return implode("\n", [
            '📎 *Boleto Disponível!*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            "📄 Contrato: *{$d['nome_contrato']}*",
            "🔢 Proposta: {$d['numero_proposta']}",
            "🏥 Operadora: {$d['operadora']}",
            "💰 Valor: {$d['valor_contrato']}",
            '',
            'Encaminhe o boleto ao cliente. Boleto em anexo.',
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            "_Alterado por: {$d['alterado_por']}_",
        ]);
    }

    private function templatePendencia(array $d): string
    {
        return implode("\n", [
            '⚠️ *Contrato em Pendência*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            "📄 Contrato: *{$d['nome_contrato']}*",
            "🔢 Proposta: {$d['numero_proposta']}",
            "🏥 Operadora: {$d['operadora']}",
            '',
            '📝 Motivo: '.($d['motivo'] !== '' ? $d['motivo'] : 'Não informado'),
            '',
            'Corrija a pendência o quanto antes para destravar a implantação.',
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            "_Alterado por: {$d['alterado_por']}_",
        ]);
    }

    private function templateEstorno(array $d): string
    {
        return implode("\n", [
            '❌ *Venda Estornada*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            "📄 Contrato: *{$d['nome_contrato']}*",
            "🔢 Proposta: {$d['numero_proposta']}",
            "💰 Valor: {$d['valor_contrato']}",
            '',
            '📝 Motivo: '.($d['motivo'] !== '' ? $d['motivo'] : 'Não informado'),
            '',
            'A venda foi devolvida ao seu painel. Abra para corrigir e reenviar ao backoffice.',
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            "_Alterado por: {$d['alterado_por']}_",
        ]);
    }

    private function templateRegularizado(array $d): string
    {
        return implode("\n", [
            '🔄 *Contrato Regularizado*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            "📄 Contrato: *{$d['nome_contrato']}*",
            "🔢 Proposta: {$d['numero_proposta']}",
            "🏥 Operadora: {$d['operadora']}",
            '',
            'A pendência foi resolvida — o contrato segue para implantação.',
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            "_Alterado por: {$d['alterado_por']}_",
        ]);
    }

    private function templateAnaliseOperadora(array $d): string
    {
        return implode("\n", [
            '🔍 *Em Análise pela Operadora*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            "📄 Contrato: *{$d['nome_contrato']}*",
            "🔢 Proposta: {$d['numero_proposta']}",
            "🏥 Operadora: {$d['operadora']}",
            '',
            'O contrato está em análise pela operadora. Acompanhe o status no painel.',
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            "_Alterado por: {$d['alterado_por']}_",
        ]);
    }

    private function templateGenerico(array $d): string
    {
        return implode("\n", [
            '🔔 *Atualização de Contrato*',
            '━━━━━━━━━━━━━━━━━━━━',
            '',
            "📄 Contrato: *{$d['nome_contrato']}*",
            "🔢 Proposta: {$d['numero_proposta']}",
            "📌 Novo status: *{$d['descricao_status']}*",
            '',
            '━━━━━━━━━━━━━━━━━━━━',
            "_Alterado por: {$d['alterado_por']}_",
        ]);
    }

    private function moeda($valor): string
    {
        if ($valor === null || $valor === '') {
            return 'R$ 0,00';
        }

        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }
}
