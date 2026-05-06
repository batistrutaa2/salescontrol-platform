<?php

namespace App\Services;

use Carbon\Carbon;

class ResumoOperacionalFormatter
{
    private const MEDALHAS = ['🥇', '🥈', '🥉'];

    public function format(array $snapshot, string $nomeAdmin, string $nomeEmpresa, Carbon $data): string
    {
        $diaSemana = $this->diaSemanaPt($data->dayOfWeek);
        $dataFmt   = $data->format('d/m/Y');
        $horaFmt   = $data->copy()->setTime(18, 0)->format('H:i');

        $vc  = $snapshot['vendas_cadastradas'];
        $vi  = $snapshot['vendas_implantadas'];
        $pi  = $snapshot['pipeline'];
        $eq  = $snapshot['equipe'];
        $top = $snapshot['top_vendedores'];
        $am  = $snapshot['amanha'];

        $linhas = [];
        $linhas[] = "📊 *Resumo Operacional — {$nomeEmpresa}*";
        $linhas[] = "📅 {$dataFmt} ({$diaSemana}) — {$horaFmt}";
        $linhas[] = '━━━━━━━━━━━━━━━━━━━━';
        $linhas[] = '';

        $linhas[] = '💰 *Vendas hoje*';
        if ($vc['count'] === 0 && $vi['count'] === 0) {
            $linhas[] = '_Sem movimento registrado hoje_';
        } else {
            $linhas[] = "• Cadastradas: *{$vc['count']}* ({$this->moeda($vc['valor_total'])})";
            $linhas[] = "• Implantadas: *{$vi['count']}* ({$this->moeda($vi['valor_total'])}) — {$vi['vidas']} vidas";
            $linhas[] = "• Ticket médio: {$this->moeda($vc['ticket_medio'])}";
        }
        $linhas[] = '';

        $linhas[] = '📈 *Pipeline*';
        $linhas[] = "📄 Boleto disponível: *{$pi['boleto_disponivel']}*";
        $linhas[] = "✅ Implantadas: *{$pi['implantadas']}*";
        $linhas[] = '';

        $linhas[] = '👥 *Equipe*';
        $linhas[] = "• Leads trabalhados: *{$eq['leads']}*";
        $linhas[] = '';

        $linhas[] = '🏆 *Top do dia*';
        if (empty($top)) {
            $linhas[] = '_Sem ranking de vendedores hoje_';
        } else {
            foreach ($top as $i => $v) {
                $medalha = self::MEDALHAS[$i] ?? '🔹';
                $nome    = $v['nome'] ?: 'Vendedor sem nome';
                $linhas[] = "{$medalha} {$nome} — {$this->moeda($v['total'])}";
            }
        }
        $linhas[] = '';

        $linhas[] = '📅 *Amanhã*';
        $linhas[] = "🤝 Agendamentos de retorno: *{$am['agendamentos']}*";
        if (empty($am['reunioes_por_gestor'])) {
            $linhas[] = '🎯 Reuniões marcadas: _nenhuma_';
        } else {
            $totalReunioes = array_sum(array_column($am['reunioes_por_gestor'], 'total'));
            $linhas[]      = "🎯 Reuniões marcadas: *{$totalReunioes}*";
            foreach ($am['reunioes_por_gestor'] as $g) {
                $nome = $g['nome'] ?: 'Gestor sem nome';
                $linhas[] = "   • {$nome}: {$g['total']}";
            }
        }
        $linhas[] = '';

        $linhas[] = '━━━━━━━━━━━━━━━━━━━━';
        $linhas[] = '_Mensagem automática — SalesControl_';

        return implode("\n", $linhas);
    }

    private function moeda(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    private function diaSemanaPt(int $dow): string
    {
        $map = [
            0 => 'domingo',
            1 => 'segunda',
            2 => 'terça',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sábado',
        ];

        return $map[$dow] ?? '';
    }
}
