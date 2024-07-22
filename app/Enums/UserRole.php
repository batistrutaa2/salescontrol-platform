<?php

namespace App\Enums;

class UserRole
{
  const VENDEDOR = 1;
  const ADMINISTRATIVO = 2;
  const BACKOFFICE = 3;
  const DEVELOPER = 4;

  private static array $ids_rules = [
    self::VENDEDOR => 1,
    self::ADMINISTRATIVO => 2,
    self::BACKOFFICE => 3,
    self::DEVELOPER => 4,
  ];

  public static function getUserRoleID(string $role): string
  {
    return self::$ids_rules[$role] ?? 'Desconhecido';
  }
}
