<?php

namespace App\Enums;

/**
 * Etapas do cancelamento do plano anterior (Painel de Cancelamentos do pós-venda).
 * As cinco primeiras são colunas do kanban, na ordem do fluxo; DESISTIDO é terminal
 * e fica fora do board (ação no modal de detalhe).
 */
enum EtapaCancelamento: string
{
    case AGUARDANDO_IMPLANTACAO = 'AGUARDANDO_IMPLANTACAO';
    case PRONTO_PARA_SOLICITAR = 'PRONTO_PARA_SOLICITAR';
    case SOLICITADO = 'SOLICITADO';
    case EM_EXIGENCIA = 'EM_EXIGENCIA';
    case CONCLUIDO = 'CONCLUIDO';
    case DESISTIDO = 'DESISTIDO';

    public function label(): string
    {
        return match ($this) {
            self::AGUARDANDO_IMPLANTACAO => 'Aguardando implantação',
            self::PRONTO_PARA_SOLICITAR => 'Pronto para solicitar',
            self::SOLICITADO => 'Solicitado',
            self::EM_EXIGENCIA => 'Em exigência',
            self::CONCLUIDO => 'Concluído',
            self::DESISTIDO => 'Desistido',
        };
    }

    public function curto(): string
    {
        return match ($this) {
            self::AGUARDANDO_IMPLANTACAO => 'Aguardando',
            self::PRONTO_PARA_SOLICITAR => 'Pronto',
            self::SOLICITADO => 'Solicitado',
            self::EM_EXIGENCIA => 'Exigência',
            self::CONCLUIDO => 'Concluído',
            self::DESISTIDO => 'Desistido',
        };
    }

    /** @return array{start:string,end:string} */
    public function gradiente(): array
    {
        return match ($this) {
            self::AGUARDANDO_IMPLANTACAO => ['start' => '#94A3B8', 'end' => '#CBD5E1'],
            self::PRONTO_PARA_SOLICITAR => ['start' => '#06B6D4', 'end' => '#22D3EE'],
            self::SOLICITADO => ['start' => '#F59E0B', 'end' => '#FBBF24'],
            self::EM_EXIGENCIA => ['start' => '#7C3AED', 'end' => '#A78BFA'],
            self::CONCLUIDO => ['start' => '#10B981', 'end' => '#34D399'],
            self::DESISTIDO => ['start' => '#EF4444', 'end' => '#F87171'],
        };
    }

    public function ehTerminal(): bool
    {
        return $this === self::CONCLUIDO || $this === self::DESISTIDO;
    }

    /**
     * Colunas do kanban (sem DESISTIDO), no formato que a view/JS consomem.
     *
     * @return array<int, array{id:string,label:string,curto:string,gradiente:array{start:string,end:string}}>
     */
    public static function colunas(): array
    {
        return array_map(
            fn (self $e) => [
                'id' => $e->value,
                'label' => $e->label(),
                'curto' => $e->curto(),
                'gradiente' => $e->gradiente(),
            ],
            array_filter(self::cases(), fn (self $e) => $e !== self::DESISTIDO)
        );
    }

    /** Valores válidos para validação de mover. @return array<int, string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
