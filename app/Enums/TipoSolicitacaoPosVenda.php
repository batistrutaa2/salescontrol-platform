<?php

namespace App\Enums;

/**
 * Tipos de solicitação da Central de Solicitações do Pós-Venda.
 * Enum dedicado (não confundir com TipoDemandaContrato, que atende a aba de
 * processos do contrato e o check-list de implantação).
 */
enum TipoSolicitacaoPosVenda: string
{
    case CANCELAMENTO = 'CANCELAMENTO';
    case PORTABILIDADE = 'PORTABILIDADE';
    case ENVIO_BOLETO = 'ENVIO_BOLETO';
    case REEMBOLSO = 'REEMBOLSO';
    case ALTERACAO_EMAIL = 'ALTERACAO_EMAIL';
    case MARCACAO_EXAME = 'MARCACAO_EXAME';
    case INCLUSAO_BENEFICIARIO = 'INCLUSAO_BENEFICIARIO';
    case EXCLUSAO_BENEFICIARIO = 'EXCLUSAO_BENEFICIARIO';
    case OUTROS = 'OUTROS';

    public function label(): string
    {
        return match ($this) {
            self::CANCELAMENTO => 'Cancelamento',
            self::PORTABILIDADE => 'Portabilidade',
            self::ENVIO_BOLETO => 'Envio de Boleto',
            self::REEMBOLSO => 'Reembolso',
            self::ALTERACAO_EMAIL => 'Alteração de E-mail',
            self::MARCACAO_EXAME => 'Marcação de Exame',
            self::INCLUSAO_BENEFICIARIO => 'Inclusão de Beneficiário',
            self::EXCLUSAO_BENEFICIARIO => 'Exclusão de Beneficiário',
            self::OUTROS => 'Outros',
        };
    }

    /**
     * Mapa tipo => label para o front, sem duplicar strings no JS.
     *
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

    /**
     * Converte um valor de TipoDemandaContrato (fluxo do vendedor legado) para
     * o tipo equivalente da central. Sem correspondência direta => OUTROS.
     */
    public static function deTipoDemanda(?string $tipo): self
    {
        return match ($tipo) {
            TipoDemandaContrato::CANCELAMENTO->value,
            TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value,
            TipoDemandaContrato::CANCELAMENTO_INTERMEDIADORA->value,
            TipoDemandaContrato::CANCELAMENTO_LIMITAR->value => self::CANCELAMENTO,
            TipoDemandaContrato::PORTABILIDADE->value => self::PORTABILIDADE,
            TipoDemandaContrato::ENVIO_BOLETO->value => self::ENVIO_BOLETO,
            TipoDemandaContrato::REEMBOLSO->value => self::REEMBOLSO,
            TipoDemandaContrato::TROCA_EMAIL->value => self::ALTERACAO_EMAIL,
            TipoDemandaContrato::INCLUSAO_BENEFICIARIO->value => self::INCLUSAO_BENEFICIARIO,
            TipoDemandaContrato::EXCLUSAO_BENEFICIARIO->value => self::EXCLUSAO_BENEFICIARIO,
            default => self::OUTROS,
        };
    }
}
