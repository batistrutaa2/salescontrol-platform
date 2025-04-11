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
  public function getRemarketingLeads(string $empresa_id);
  public function getTabulationId($idMailing);
  public function transferContact(array $data);
  public function sendRemaketing($idLead, $sub_tabulacao_id);
  public function sendSchedule($idLead);
  public function alterStatusContract($contato_id, $tabulacao_id): bool;
  public function getQueueCurrent($id_user);
  public function deleteMailing($id_mailing);
}
