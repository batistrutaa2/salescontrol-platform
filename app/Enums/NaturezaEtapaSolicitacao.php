<?php

namespace App\Enums;

/**
 * Semântica de encerramento das etapas configuráveis do fluxo de solicitações:
 * mover um card para etapa CONCLUIDA/CANCELADA encerra a solicitação; voltar
 * para uma etapa EM_ANDAMENTO reabre.
 */
enum NaturezaEtapaSolicitacao: string
{
    case EM_ANDAMENTO = 'EM_ANDAMENTO';
    case CONCLUIDA = 'CONCLUIDA';
    case CANCELADA = 'CANCELADA';

    public function label(): string
    {
        return match ($this) {
            self::EM_ANDAMENTO => 'Em andamento',
            self::CONCLUIDA => 'Concluída',
            self::CANCELADA => 'Cancelada',
        };
    }

    public function encerra(): bool
    {
        return $this !== self::EM_ANDAMENTO;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = $case->label();
        }

        return $map;
    }
}
