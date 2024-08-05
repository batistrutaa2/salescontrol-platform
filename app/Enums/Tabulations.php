<?php

namespace App\Enums;

class Tabulations
{
  const PROSPECCAO = 1;
  const REUNIÃO = 2;
  const NEGOCIAÇÃO = 3;
  const DOCUMENTO = 4;
  const NEGOCIO_FECHADO = 5;
  const NEGOCIO_NAO_FECHADO = 6;
  const REMARKETING = 10;



  private static array $ids_rules = [
    self::PROSPECCAO => 1,
    self::REUNIÃO => 2,
    self::NEGOCIAÇÃO => 3,
    self::DOCUMENTO => 4,
    self::NEGOCIO_FECHADO => 5,
    self::NEGOCIO_NAO_FECHADO => 6,
    self::REMARKETING => 10,
  ];

  public static function getUserRoleID(string $role): string
  {
    return self::$ids_rules[$role] ?? self::PROSPECCAO;
  }
}
