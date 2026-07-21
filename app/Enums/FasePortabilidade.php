<?php

namespace App\Enums;

/**
 * Fases do processo de portabilidade, em ordem. CONCLUIDA e NEGADA são finais
 * (fecham o processo); NEGADA registra o desfecho negativo sem perdê-lo.
 */
enum FasePortabilidade: string
{
    case REUNINDO_DOCUMENTOS = 'REUNINDO_DOCUMENTOS';
    case ENVIADA_ANALISE = 'ENVIADA_ANALISE';
    case CONCLUIDA = 'CONCLUIDA';
    case NEGADA = 'NEGADA';

    public function label(): string
    {
        return match ($this) {
            self::REUNINDO_DOCUMENTOS => 'Reunindo documentos',
            self::ENVIADA_ANALISE => 'Enviada para análise',
            self::CONCLUIDA => 'Concluída',
            self::NEGADA => 'Negada',
        };
    }

    public function ehFinal(): bool
    {
        return $this === self::CONCLUIDA || $this === self::NEGADA;
    }

    /** Fases em ordem, para o front montar o seletor/stepper. @return array<int, array{value:string,label:string,final:bool}> */
    public static function fluxo(): array
    {
        return array_map(
            fn (self $f) => ['value' => $f->value, 'label' => $f->label(), 'final' => $f->ehFinal()],
            self::cases()
        );
    }
}
