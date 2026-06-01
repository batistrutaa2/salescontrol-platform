<?php

namespace App\Enums;

enum TipoDemandaContrato: string
{
    case ACESSO_EMPRESA = 'ACESSO_EMPRESA';
    case LOGIN_APPS = 'LOGIN_APPS';
    case TROCA_EMAIL = 'TROCA_EMAIL';
    case CANCELAMENTO_QUALICORP = 'CANCELAMENTO_QUALICORP';
    case CANCELAMENTO_LIMITAR = 'CANCELAMENTO_LIMITAR';
    case BOAS_VINDAS = 'BOAS_VINDAS';
    case ENVIO_BOLETO = 'ENVIO_BOLETO';
    case REEMBOLSO = 'REEMBOLSO';
    case INCLUSAO_BENEFICIARIO = 'INCLUSAO_BENEFICIARIO';
    case EXCLUSAO_BENEFICIARIO = 'EXCLUSAO_BENEFICIARIO';
    case CANCELAMENTO = 'CANCELAMENTO';
    case CARTA_PERMANENCIA = 'CARTA_PERMANENCIA';
    case PORTABILIDADE = 'PORTABILIDADE';
    case OUTRO = 'OUTRO';

    public function label(): string
    {
        return match ($this) {
            self::ACESSO_EMPRESA => 'Acesso da Empresa',
            self::LOGIN_APPS => 'Login de Apps',
            self::TROCA_EMAIL => 'Alteração de E-mail',
            self::CANCELAMENTO_QUALICORP => 'Cancelamento Qualicorp',
            self::CANCELAMENTO_LIMITAR => 'Cancelamento via Limitar',
            self::BOAS_VINDAS => 'Boas-vindas',
            self::ENVIO_BOLETO => 'Envio de Boleto',
            self::REEMBOLSO => 'Solicitação de Reembolso',
            self::INCLUSAO_BENEFICIARIO => 'Inclusão de Beneficiário',
            self::EXCLUSAO_BENEFICIARIO => 'Exclusão de Beneficiário',
            self::CANCELAMENTO => 'Cancelamento',
            self::CARTA_PERMANENCIA => 'Carta de Permanência',
            self::PORTABILIDADE => 'Portabilidade',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Mapa tipo => label, para expor ao front (badges) sem duplicar strings no JS.
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
}
