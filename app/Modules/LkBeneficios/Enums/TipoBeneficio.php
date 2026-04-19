<?php

namespace App\Modules\LkBeneficios\Enums;

class TipoBeneficio
{
    const VIDA = 'VIDA';
    const ODONTO = 'ODONTO';
    const PREVIDENCIA = 'PREVIDENCIA';
    const PATRIMONIAL = 'PATRIMONIAL';

    public static function all(): array
    {
        return [
            self::VIDA,
            self::ODONTO,
            self::PREVIDENCIA,
            self::PATRIMONIAL,
        ];
    }

    public static function label(string $tipo): string
    {
        return match ($tipo) {
            self::VIDA => 'Seguro de Vida',
            self::ODONTO => 'Odontológico',
            self::PREVIDENCIA => 'Previdência Privada',
            self::PATRIMONIAL => 'Seguros Patrimoniais',
            default => $tipo,
        };
    }

    public static function icon(string $tipo): string
    {
        return match ($tipo) {
            self::VIDA => 'ri-heart-pulse-line',
            self::ODONTO => 'ri-mental-health-line',
            self::PREVIDENCIA => 'ri-safe-2-line',
            self::PATRIMONIAL => 'ri-home-4-line',
            default => 'ri-shield-line',
        };
    }
}
