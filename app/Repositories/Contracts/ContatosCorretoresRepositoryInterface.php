<?php

namespace App\Repositories\Contracts;

interface ContatosCorretoresRepositoryInterface
{
  public function getClientComercial(string $rulerUser, string $empresa_id);
  public function changeStatusLead($data): bool;
  public function updateLeadTemperature($idMailing, $temperatura);
}
