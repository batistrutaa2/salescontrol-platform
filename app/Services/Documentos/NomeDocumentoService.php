<?php

namespace App\Services\Documentos;

use Illuminate\Support\Str;

class NomeDocumentoService
{
    private const RESERVADOS_WINDOWS = [
        'CON', 'PRN', 'AUX', 'NUL',
        'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
        'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
    ];

    public function segmento(string $valor, string $fallback = 'Sem nome'): string
    {
        $valor = preg_replace('~[<>:"/\\\\|?*\x00-\x1F]~u', ' ', $valor) ?? '';
        $valor = preg_replace('/\s+/u', ' ', trim($valor, " .\t\n\r\0\x0B")) ?? '';
        $valor = Str::limit($valor, 120, '');
        $valor = trim($valor, ' .');

        if ($valor === '' || in_array(mb_strtoupper($valor), self::RESERVADOS_WINDOWS, true)) {
            return $fallback;
        }

        return $valor;
    }

    public function arquivo(string $nome): string
    {
        $extensao = pathinfo($nome, PATHINFO_EXTENSION);
        $base = pathinfo($nome, PATHINFO_FILENAME);
        $base = $this->segmento($base, 'documento');
        $extensao = preg_replace('/[^a-zA-Z0-9]/', '', $extensao) ?? '';

        return $extensao === '' ? $base : $base.'.'.mb_strtolower($extensao);
    }

    public function normalizado(string $nome): string
    {
        $nome = preg_replace('~^.*[\\\\/]~u', '', $nome) ?? $nome;
        $nome = \Normalizer::normalize($nome, \Normalizer::FORM_C) ?: $nome;
        $nome = preg_replace('/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '', $nome) ?? '';
        $nome = preg_replace('/[\p{Z}\s]+/u', ' ', $nome) ?? '';

        return mb_strtolower($nome, 'UTF-8');
    }

    public function comSufixo(string $nome, int $sequencia): string
    {
        $extensao = pathinfo($nome, PATHINFO_EXTENSION);
        $base = pathinfo($nome, PATHINFO_FILENAME);
        $sufixo = " - {$sequencia}";

        return Str::limit($base, 120 - strlen($sufixo), '').$sufixo.($extensao ? ".{$extensao}" : '');
    }
}
