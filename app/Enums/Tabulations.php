<?php

namespace App\Enums;

class Tabulations
{
  // Tabulações de Vendas
  const PROSPECCAO = 1;
  const REUNIÃO = 2;
  const NEGOCIAÇÃO = 3;
  const DOCUMENTO = 4;
  const NEGOCIO_FECHADO = 5;
  const NEGOCIO_NAO_FECHADO = 6;
  const REMARKETING = 10;
  const SEM_CONTATO = 19;
  const NOVOS_CLIENTES = 50;
  const AGENDAMENTO = 29;
  const FOLLOWUP = 13;

  // Tabulações de Pós-Vendas - conta como cadastrado
  const VENDA = 16;
  const IMPLANTADO = 18;
  const PENDENCIA = 55;
  const ANALISE_OPERADORA = 59;
  const BOLETO_DISPONIVEL = 58;
  const REGULARIZADO = 57;
  const CONTR_GERADO_AGUARDANDO_ASSINATURA = 56;
  const ANALISE_DOCUMENTOS = 54;
  const AGUARD_ASSINATURA_DS = 60;

  /** Status em que o contrato já pode receber solicitações de pós-venda. */
  const STATUS_ELEGIVEIS_SOLICITACAO_POS_VENDA = [
    self::VENDA,
    self::ANALISE_DOCUMENTOS,
    self::CONTR_GERADO_AGUARDANDO_ASSINATURA,
    self::AGUARD_ASSINATURA_DS,
    self::ANALISE_OPERADORA,
    self::PENDENCIA,
    self::BOLETO_DISPONIVEL,
    self::REGULARIZADO,
    self::IMPLANTADO,
  ];

  // Tabulações de Suporte - não conta como cadastrado
  const ESTORNO = 17;
  const DECLINIO = 53;


  private static array $ids_rules = [
    self::PROSPECCAO => 1,
    self::REUNIÃO => 2,
    self::NEGOCIAÇÃO => 3,
    self::DOCUMENTO => 4,
    self::NEGOCIO_FECHADO => 5,
    self::NEGOCIO_NAO_FECHADO => 6,
    self::REMARKETING => 10,
    self::SEM_CONTATO => 19,
    self::VENDA => 16,
    self::IMPLANTADO => 18,
    self::ESTORNO => 17,
    self::AGENDAMENTO => 29,
    self::FOLLOWUP => 13
  ];

  public static function getUserRoleID(string $role): string
  {
    return self::$ids_rules[$role] ?? self::PROSPECCAO;
  }
}
