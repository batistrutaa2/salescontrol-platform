<?php

namespace App\Services;

use App\Models\ComercialReunioes;
use Carbon\Carbon;

class ReuniaoAgendadaFormatter
{
    public function format(ComercialReunioes $reuniao): string
    {
        $vendedor = $reuniao->user?->name ?: 'Vendedor';
        $gestor = $reuniao->manager?->name ?: 'Gestor';
        $titulo = $reuniao->titulo ?: 'Reunião';
        $location = $reuniao->location;
        $observacao = trim((string) ($reuniao->observacao ?? ''));

        $inicio = Carbon::parse($reuniao->data_inicio);
        $fim = Carbon::parse($reuniao->data_final);

        $modalidade = $this->detectarModalidade($location);
        $localTexto = $location !== null && $location !== ''
            ? $location.' _('.$modalidade.')_'
            : '— _(modalidade não informada)_';

        $linhas = [];
        $linhas[] = '🗓️ *Nova reunião agendada*';
        $linhas[] = '━━━━━━━━━━━━━━━━━━━━';
        $linhas[] = '';
        $linhas[] = "Olá, *{$gestor}*!";
        $linhas[] = "*{$vendedor}* agendou uma reunião com você.";
        $linhas[] = '';
        $linhas[] = "📌 *{$titulo}*";
        $linhas[] = '📅 '.$inicio->format('d/m/Y').' ('.$this->diaSemanaPt($inicio->dayOfWeek).')';
        $linhas[] = '🕐 '.$inicio->format('H:i').' → '.$fim->format('H:i');
        $linhas[] = '📍 Local: '.$localTexto;
        $linhas[] = '📝 Observações: '.($observacao !== '' ? $observacao : '—');
        $linhas[] = '';
        $linhas[] = '━━━━━━━━━━━━━━━━━━━━';
        $linhas[] = '_Mensagem automática — SalesControl_';

        return implode("\n", $linhas);
    }

    private function detectarModalidade(?string $location): string
    {
        if ($location === null || trim($location) === '') {
            return 'modalidade não informada';
        }

        $padrao = '/(https?:\/\/|meet\.|zoom\.|teams\.|whereby\.|webex\.)/i';

        return preg_match($padrao, $location) === 1 ? 'Virtual' : 'Presencial';
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
