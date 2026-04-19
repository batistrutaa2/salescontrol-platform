<?php

namespace App\Modules\LkBeneficios\Enums;

class StatusContrato
{
    const EM_IMPLANTACAO = 'EM_IMPLANTACAO';
    const ATIVO = 'ATIVO';
    const SUSPENSO = 'SUSPENSO';
    const CANCELADO = 'CANCELADO';

    public static function all(): array
    {
        return [
            self::EM_IMPLANTACAO,
            self::ATIVO,
            self::SUSPENSO,
            self::CANCELADO,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::EM_IMPLANTACAO => 'Em Implantação',
            self::ATIVO => 'Ativo',
            self::SUSPENSO => 'Suspenso',
            self::CANCELADO => 'Cancelado',
            default => $status,
        };
    }

    public static function badgeClass(string $status): string
    {
        return match ($status) {
            self::EM_IMPLANTACAO => 'bg-label-warning',
            self::ATIVO => 'bg-label-success',
            self::SUSPENSO => 'bg-label-secondary',
            self::CANCELADO => 'bg-label-danger',
            default => 'bg-label-secondary',
        };
    }
}
