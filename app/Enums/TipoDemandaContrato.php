<?php

namespace App\Enums;

enum TipoDemandaContrato: string
{
    case ACESSO_EMPRESA = 'ACESSO_EMPRESA';
    case LOGIN_APPS = 'LOGIN_APPS';
    case TROCA_EMAIL = 'TROCA_EMAIL';
    case CANCELAMENTO_INTERMEDIADORA = 'CANCELAMENTO_INTERMEDIADORA';
    case CANCELAMENTO_LIMITAR = 'CANCELAMENTO_LIMITAR';
    case BOAS_VINDAS = 'BOAS_VINDAS';
    case ENVIO_BOLETO = 'ENVIO_BOLETO';
    case REEMBOLSO = 'REEMBOLSO';
    case INCLUSAO_BENEFICIARIO = 'INCLUSAO_BENEFICIARIO';
    case EXCLUSAO_BENEFICIARIO = 'EXCLUSAO_BENEFICIARIO';
    case CANCELAMENTO = 'CANCELAMENTO';
    case CANCELAMENTO_OPERADORA_ANTERIOR = 'CANCELAMENTO_OPERADORA_ANTERIOR';
    case CARTA_PERMANENCIA = 'CARTA_PERMANENCIA';
    case PORTABILIDADE = 'PORTABILIDADE';
    case DS = 'DS';
    case EMAIL_IMPLANTACAO = 'EMAIL_IMPLANTACAO';
    case ACESSO_BENEFICIARIO = 'ACESSO_BENEFICIARIO';
    case OUTRO = 'OUTRO';

    public function label(): string
    {
        return match ($this) {
            self::ACESSO_EMPRESA => 'Acesso da Empresa',
            self::LOGIN_APPS => 'Login de Apps',
            self::TROCA_EMAIL => 'Alteração de E-mail',
            self::CANCELAMENTO_INTERMEDIADORA => 'Cancelamento na Intermediadora',
            self::CANCELAMENTO_LIMITAR => 'Cancelamento via Limitar',
            self::BOAS_VINDAS => 'Boas-vindas',
            self::ENVIO_BOLETO => 'Envio de Boleto',
            self::REEMBOLSO => 'Solicitação de Reembolso',
            self::INCLUSAO_BENEFICIARIO => 'Inclusão de Beneficiário',
            self::EXCLUSAO_BENEFICIARIO => 'Exclusão de Beneficiário',
            self::CANCELAMENTO => 'Cancelamento',
            self::CANCELAMENTO_OPERADORA_ANTERIOR => 'Cancelamento na Operadora Anterior',
            self::CARTA_PERMANENCIA => 'Carta de Permanência',
            self::PORTABILIDADE => 'Portabilidade',
            self::DS => 'Declaração de Saúde (DS)',
            self::EMAIL_IMPLANTACAO => 'E-mail de Implantação',
            self::ACESSO_BENEFICIARIO => 'Acesso do Beneficiário',
            self::OUTRO => 'Outro',
        };
    }

    /**
     * Agrupa cada tipo numa aba da tela de acompanhamento de processos.
     * O JS lê este mapa para distribuir os processos nas abas sem duplicar regra.
     *
     * @return array<string, string>
     */
    public static function grupos(): array
    {
        $cancelamentos = [
            self::CANCELAMENTO, self::CANCELAMENTO_OPERADORA_ANTERIOR,
            self::CANCELAMENTO_INTERMEDIADORA, self::CANCELAMENTO_LIMITAR, self::CARTA_PERMANENCIA,
        ];
        $acessos = [self::ACESSO_EMPRESA, self::LOGIN_APPS, self::ACESSO_BENEFICIARIO];

        $map = [];
        foreach ($cancelamentos as $c) {
            $map[$c->value] = 'cancelamentos';
        }
        foreach ($acessos as $a) {
            $map[$a->value] = 'acessos';
        }
        $map[self::EMAIL_IMPLANTACAO->value] = 'emails';
        $map[self::PORTABILIDADE->value] = 'portabilidade';
        $map[self::DS->value] = 'ds';

        // Os demais (boleto, reembolso, inclusão/exclusão, outro) caem em "outros".
        foreach (self::cases() as $case) {
            $map[$case->value] ??= 'outros';
        }

        return $map;
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
