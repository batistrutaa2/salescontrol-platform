<?php

namespace App\Modules\LkBeneficios\Enums;

class StatusLead
{
    const NOVO_CLIENTE = 'NOVO_CLIENTE';
    const PROSPECTADO = 'PROSPECTADO';
    const SEM_CONTATO = 'SEM_CONTATO';
    const NEGOCIANDO = 'NEGOCIANDO';
    const FOLLOW_UP = 'FOLLOW_UP';
    const DOCUMENTACAO = 'DOCUMENTACAO';

    public static function all(): array
    {
        return [
            self::NOVO_CLIENTE,
            self::PROSPECTADO,
            self::SEM_CONTATO,
            self::NEGOCIANDO,
            self::FOLLOW_UP,
            self::DOCUMENTACAO,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::NOVO_CLIENTE => 'Novo Cliente',
            self::PROSPECTADO => 'Prospectado',
            self::SEM_CONTATO => 'Sem Contato',
            self::NEGOCIANDO => 'Negociando',
            self::FOLLOW_UP => 'Follow-up',
            self::DOCUMENTACAO => 'Documentação',
            default => $status,
        };
    }

    public static function ordem(string $status): int
    {
        return array_search($status, self::all(), true) ?: 0;
    }

    public static function corGradiente(string $status): array
    {
        return match ($status) {
            self::NOVO_CLIENTE => ['#06B6D4', '#22D3EE'],
            self::PROSPECTADO => ['#7C3AED', '#A78BFA'],
            self::SEM_CONTATO => ['#9CA3AF', '#D1D5DB'],
            self::NEGOCIANDO => ['#F59E0B', '#FBBF24'],
            self::FOLLOW_UP => ['#EF4444', '#F87171'],
            self::DOCUMENTACAO => ['#10B981', '#34D399'],
            default => ['#9CA3AF', '#D1D5DB'],
        };
    }
}
