<?php

namespace App\Enums;

final class RenovacaoStatus
{
    public const ELEGIVEL = 'ELEGIVEL';
    public const AGUARDANDO_RESPOSTA = 'AGUARDANDO_RESPOSTA';
    public const EM_CONVERSA = 'EM_CONVERSA';
    public const SEM_RESPOSTA = 'SEM_RESPOSTA';
    public const REAGENDADO = 'REAGENDADO';
    public const COTACAO_SOLICITADA = 'COTACAO_SOLICITADA';
    public const SEM_INTERESSE = 'SEM_INTERESSE';
    public const NAO_CONTATAR = 'NAO_CONTATAR';
    public const CONVERTIDO = 'CONVERTIDO';
    public const SUSPENSO = 'SUSPENSO';

    public static function tratativas(): array
    {
        return [
            self::AGUARDANDO_RESPOSTA,
            self::EM_CONVERSA,
            self::SEM_RESPOSTA,
            self::REAGENDADO,
            self::COTACAO_SOLICITADA,
            self::SEM_INTERESSE,
            self::NAO_CONTATAR,
        ];
    }
}
