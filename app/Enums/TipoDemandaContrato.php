<?php

namespace App\Enums;

enum TipoDemandaContrato: string
{
  case CANCELAMENTO = 'CANCELAMENTO';
  case CARTA_PERMANENCIA = 'CARTA_PERMANENCIA';
  case PORTABILIDADE = 'PORTABILIDADE';
  case TROCA_EMAIL = 'TROCA_EMAIL';
  case OUTRO = 'OUTRO';

  public function label(): string
  {
    return match ($this) {
      self::CANCELAMENTO => 'Cancelamento',
      self::CARTA_PERMANENCIA => 'Carta de Permanência',
      self::PORTABILIDADE => 'Portabilidade',
      self::TROCA_EMAIL => 'Troca de E-mail',
      self::OUTRO => 'Outro',
    };
  }
}
