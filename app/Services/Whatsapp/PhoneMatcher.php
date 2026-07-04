<?php

namespace App\Services\Whatsapp;

class PhoneMatcher
{
    /**
     * Normaliza telefone brasileiro para o formato canônico de matching:
     * DDD (2 dígitos) + últimos 8 dígitos do número.
     *
     * Elimina a ambiguidade do código do país (55) e do 9º dígito:
     * "5585988887777", "85988887777" e "8588887777" normalizam para "8588887777".
     */
    public static function normalizar(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $raw);

        if (str_starts_with($digitos, '55') && strlen($digitos) >= 12) {
            $digitos = substr($digitos, 2);
        }

        if (strlen($digitos) < 10 || strlen($digitos) > 11) {
            return null;
        }

        return substr($digitos, 0, 2).substr($digitos, -8);
    }

    /**
     * Extrai o número (só dígitos, com 55) de um remoteJid da Evolution.
     * Ex: "5585988887777@s.whatsapp.net" => "5585988887777"
     */
    public static function numeroDoJid(string $remoteJid): string
    {
        return preg_replace('/\D/', '', strtok($remoteJid, '@'));
    }

    /**
     * Resolve o remoteJid efetivo de uma key de mensagem. O WhatsApp usa o
     * formato @lid para alguns contatos — nesse caso o número real vem em
     * senderPn/remoteJidAlt/previousRemoteJid.
     */
    public static function jidEfetivo(array $key): ?string
    {
        $remoteJid = $key['remoteJid'] ?? null;

        if ($remoteJid && str_ends_with($remoteJid, '@lid')) {
            return $key['senderPn']
                ?? $key['remoteJidAlt']
                ?? $key['previousRemoteJid']
                ?? $remoteJid;
        }

        return $remoteJid;
    }

    public static function isGrupo(string $remoteJid): bool
    {
        return str_ends_with($remoteJid, '@g.us');
    }

    public static function isBroadcast(string $remoteJid): bool
    {
        return str_contains($remoteJid, '@broadcast') || str_contains($remoteJid, '@newsletter');
    }
}
