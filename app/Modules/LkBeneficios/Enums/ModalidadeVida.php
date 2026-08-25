<?php

namespace App\Modules\LkBeneficios\Enums;

final class ModalidadeVida
{
    public const TRADICIONAL = 'TRADICIONAL';

    public const VITALICIO = 'VITALICIO';

    public const RESGATAVEL = 'RESGATAVEL';

    public static function all(): array
    {
        return [self::TRADICIONAL, self::VITALICIO, self::RESGATAVEL];
    }

    public static function label(?string $value): ?string
    {
        return match ($value) {
            self::TRADICIONAL => 'Tradicional',
            self::VITALICIO => 'Vitalício',
            self::RESGATAVEL => 'Resgatável',
            default => null,
        };
    }
}
