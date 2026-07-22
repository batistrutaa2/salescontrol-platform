<?php

namespace App\Enums;

/**
 * Fases pelas quais um processo de cancelamento passa, em ordem.
 * A última (CONCLUIDO) marca a demanda como CONCLUIDA.
 */
enum FaseCancelamento: string
{
    case SOLICITADO = 'SOLICITADO';
    case PROTOCOLADO = 'PROTOCOLADO';
    case EM_ANALISE = 'EM_ANALISE';
    case CONCLUIDO = 'CONCLUIDO';

    public function label(): string
    {
        return match ($this) {
            self::SOLICITADO => 'Solicitado',
            self::PROTOCOLADO => 'Protocolado',
            self::EM_ANALISE => 'Em análise',
            self::CONCLUIDO => 'Concluído',
        };
    }

    public function ordem(): int
    {
        return match ($this) {
            self::SOLICITADO => 1,
            self::PROTOCOLADO => 2,
            self::EM_ANALISE => 3,
            self::CONCLUIDO => 4,
        };
    }

    public function ehFinal(): bool
    {
        return $this === self::CONCLUIDO;
    }

    /** Fases em ordem, para o front montar o stepper. @return array<int, array{value:string,label:string,final:bool}> */
    public static function fluxo(): array
    {
        return array_map(
            fn (self $f) => ['value' => $f->value, 'label' => $f->label(), 'final' => $f->ehFinal()],
            self::cases()
        );
    }
}
