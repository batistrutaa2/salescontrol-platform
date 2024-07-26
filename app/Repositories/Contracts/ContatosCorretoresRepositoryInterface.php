<?php

namespace App\Repositories\Contracts;

use Dotenv\Util\Str;

interface ContatosCorretoresRepositoryInterface
{
  public function getClientComercial(string $rulerUser, string $empresa_id);
  public function changeStatusLead($data): bool;
  public function updateLeadTemperature($idMailing, $temperatura);
  public function getClientInfo($idMailing);
  public function updateTemperatureAndTabulation(string $temperatura, string $idMailing, string $tabulacao_id);
}
