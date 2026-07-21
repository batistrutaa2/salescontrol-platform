<?php

namespace App\Enums;

/**
 * Como o cancelamento do plano anterior é feito.
 * Definido pelo backoffice no acompanhamento do processo.
 */
enum ModalidadeCancelamento: string
{
    case DIRETO_OPERADORA = 'DIRETO_OPERADORA';
    case VIA_LIMITAR = 'VIA_LIMITAR';

    public function label(): string
    {
        return match ($this) {
            self::DIRETO_OPERADORA => 'Direto na operadora',
            self::VIA_LIMITAR => 'Via Limitar',
        };
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = $case->label();
        }

        return $map;
    }
}
