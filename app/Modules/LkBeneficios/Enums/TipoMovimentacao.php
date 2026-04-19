<?php

namespace App\Modules\LkBeneficios\Enums;

class TipoMovimentacao
{
    const INCLUSAO = 'INCLUSAO';
    const EXCLUSAO = 'EXCLUSAO';
    const REAJUSTE = 'REAJUSTE';
    const ALTERACAO_DADOS = 'ALTERACAO_DADOS';
    const REATIVACAO = 'REATIVACAO';
    const CANCELAMENTO = 'CANCELAMENTO';

    public static function all(): array
    {
        return [
            self::INCLUSAO,
            self::EXCLUSAO,
            self::REAJUSTE,
            self::ALTERACAO_DADOS,
            self::REATIVACAO,
            self::CANCELAMENTO,
        ];
    }

    public static function label(string $tipo): string
    {
        return match ($tipo) {
            self::INCLUSAO => 'Inclusão',
            self::EXCLUSAO => 'Exclusão',
            self::REAJUSTE => 'Reajuste',
            self::ALTERACAO_DADOS => 'Alteração de Dados',
            self::REATIVACAO => 'Reativação',
            self::CANCELAMENTO => 'Cancelamento',
            default => $tipo,
        };
    }
}
