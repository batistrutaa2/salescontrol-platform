<?php

namespace App\Enums;

final class TabulationCode
{
    public const PROSPECCAO = 'PROSPECCAO';

    public const REUNIAO = 'REUNIAO';

    public const NEGOCIACAO = 'NEGOCIACAO';

    public const DOCUMENTO = 'DOCUMENTO';

    public const NEGOCIO_FECHADO = 'NEGOCIO_FECHADO';

    public const NEGOCIO_NAO_FECHADO = 'NEGOCIO_NAO_FECHADO';

    public const REMARKETING = 'REMARKETING';

    public const FOLLOW_UP = 'FOLLOW_UP';

    public const SEM_CONTATO = 'SEM_CONTATO';

    public const NOVOS_CLIENTES = 'NOVOS_CLIENTES';

    public const AGENDAMENTO = 'AGENDAMENTO';

    public const VENDA = 'VENDA';

    public const ESTORNO = 'ESTORNO';

    public const IMPLANTADO = 'IMPLANTADO';

    public const DECLINADO = 'DECLINADO';

    public const ANALISE_DOCUMENTOS = 'ANALISE_DOCUMENTOS';

    public const PENDENCIA = 'PENDENCIA';

    public const CONTRATO_GERADO_AGUARDANDO_ASSINATURA = 'CONTRATO_GERADO_AGUARDANDO_ASSINATURA';

    public const REGULARIZADO = 'REGULARIZADO';

    public const BOLETO_DISPONIVEL = 'BOLETO_DISPONIVEL';

    public const ANALISE_OPERADORA = 'ANALISE_OPERADORA';

    public const AGUARDANDO_ASSINATURA_DS = 'AGUARDANDO_ASSINATURA_DS';

    public const POS_VENDA_ELEGIVEIS = [
        self::VENDA,
        self::ANALISE_DOCUMENTOS,
        self::CONTRATO_GERADO_AGUARDANDO_ASSINATURA,
        self::AGUARDANDO_ASSINATURA_DS,
        self::ANALISE_OPERADORA,
        self::PENDENCIA,
        self::BOLETO_DISPONIVEL,
        self::REGULARIZADO,
        self::IMPLANTADO,
    ];

    public const COMERCIAL_ATIVO = [
        self::NOVOS_CLIENTES,
        self::PROSPECCAO,
        self::SEM_CONTATO,
        self::NEGOCIACAO,
        self::FOLLOW_UP,
        self::REUNIAO,
        self::DOCUMENTO,
        self::NEGOCIO_FECHADO,
        self::NEGOCIO_NAO_FECHADO,
    ];

    public static function defaults(): array
    {
        return [
            self::NOVOS_CLIENTES => ['descricao' => 'NOVOS CLIENTES', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'A', 'status' => 'Y'],
            self::PROSPECCAO => ['descricao' => 'PROSPECÇÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'B', 'status' => 'Y'],
            self::SEM_CONTATO => ['descricao' => 'SEM CONTATO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'C', 'status' => 'Y'],
            self::NEGOCIACAO => ['descricao' => 'NEGOCIAÇÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'D', 'status' => 'Y'],
            self::FOLLOW_UP => ['descricao' => 'FOLLOW-UP', 'tipo_tabulacao' => 'C', 'efetivo' => 'N', 'ordem_kanban' => 'E', 'status' => 'Y'],
            self::REUNIAO => ['descricao' => 'REUNIÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'F', 'status' => 'Y'],
            self::DOCUMENTO => ['descricao' => 'DOCUMENTO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'G', 'status' => 'Y'],
            self::NEGOCIO_FECHADO => ['descricao' => 'NEGÓCIO FECHADO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'H', 'status' => 'N'],
            self::NEGOCIO_NAO_FECHADO => ['descricao' => 'NEGÓCIO NÃO FECHADO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'I', 'status' => 'N'],
            self::REMARKETING => ['descricao' => 'REMARKETING', 'tipo_tabulacao' => 'A', 'efetivo' => 'N', 'ordem_kanban' => 'G', 'status' => 'Y'],
            self::AGENDAMENTO => ['descricao' => 'AGENDAMENTO', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => 'A', 'status' => 'Y'],
            self::VENDA => ['descricao' => 'VENDA', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => 'A', 'status' => 'Y'],
            self::ESTORNO => ['descricao' => 'ESTORNO', 'tipo_tabulacao' => 'A', 'efetivo' => 'N', 'ordem_kanban' => 'A', 'status' => 'Y'],
            self::IMPLANTADO => ['descricao' => 'IMPLANTADO', 'tipo_tabulacao' => 'A', 'efetivo' => 'N', 'ordem_kanban' => 'A', 'status' => 'Y'],
            self::DECLINADO => ['descricao' => 'DECLINADO', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y'],
            self::ANALISE_DOCUMENTOS => ['descricao' => 'ANÁLISE DE DOCUMENTOS', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y', 'prazo' => '48 HORAS'],
            self::PENDENCIA => ['descricao' => 'PENDÊNCIA', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y', 'prazo' => '48 HORAS'],
            self::CONTRATO_GERADO_AGUARDANDO_ASSINATURA => ['descricao' => 'CONTRATO GERADO - AGUARDANDO ASSINATURA', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y'],
            self::REGULARIZADO => ['descricao' => 'REGULARIZADO', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y'],
            self::BOLETO_DISPONIVEL => ['descricao' => 'BOLETO DISPONÍVEL', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y', 'prazo' => '48 HORAS'],
            self::ANALISE_OPERADORA => ['descricao' => 'ANÁLISE DA OPERADORA', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y', 'prazo' => '10 DIAS'],
            self::AGUARDANDO_ASSINATURA_DS => ['descricao' => 'AGUARDANDO ASSINATURA DA DS', 'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'ordem_kanban' => null, 'status' => 'Y', 'prazo' => '48 HORAS'],
        ];
    }
}
