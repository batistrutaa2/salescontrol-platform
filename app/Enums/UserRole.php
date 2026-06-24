<?php

namespace App\Enums;

class UserRole
{
  const VENDEDOR = 1;
  const ADMINISTRATIVO = 2;
  const BACKOFFICE = 3;
  const DEVELOPER = 4;
  const SUPERVISOR = 5;
  const BENEFICIOS = 6;
  const ADVOGADA = 7;

  private static array $ids_rules = [
    self::VENDEDOR => 1,
    self::ADMINISTRATIVO => 2,
    self::BACKOFFICE => 3,
    self::DEVELOPER => 4,
    self::SUPERVISOR => 5,
    self::BENEFICIOS => 6,
    self::ADVOGADA => 7,
  ];

  public static function getUserRoleID(string $role): string
  {
    return self::$ids_rules[$role] ?? 'Desconhecido';
  }
}
