<?php

namespace App\Repositories\Contracts;

interface ContatosCorretoresRepositoryInterface
{
  public function getClientComercial(string $rulerUser, string $empresa_id);
  public function changeStatusLead($data): bool;
  public function updateLeadTemperature($idMailing, $temperatura);
  public function getClientInfo($idMailing);
  public function updateTemperature(string $temperatura, string $idMailing);
}
