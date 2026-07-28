<?php

namespace App\Enums;

/**
 * Via pela qual o cancelamento do plano anterior é solicitado.
 * Registro informativo do painel — não há integração com a tela de liminar da advogada.
 */
enum ViaCancelamento: string
{
    case DIRETO_OPERADORA = 'DIRETO_OPERADORA'; // Bradesco, SulAmérica, Qualicorp
    case LIMINAR = 'LIMINAR';                   // demais operadoras (advogada, cancelamento imediato)

    public function label(): string
    {
        return match ($this) {
            self::DIRETO_OPERADORA => 'Direto na operadora',
            self::LIMINAR => 'Via liminar',
        };
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        $out = [];
        foreach (self::cases() as $via) {
            $out[$via->value] = $via->label();
        }

        return $out;
    }

    /** @return array<int, string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
