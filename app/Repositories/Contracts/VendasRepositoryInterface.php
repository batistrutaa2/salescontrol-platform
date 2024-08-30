<?php

namespace App\Repositories\Contracts;

interface VendasRepositoryInterface
{
  public function  create(array $data);
  public function  all();
  public function vendasDoMesAnoAtual($user_id, $empresa_id, $role_user_id);
  public function totalVendasCadastradasAnoMesAtual($user_id, $empresa_id, $role_user_id);
  public function totalVendasImplantadasAnoMesAtual($user_id, $empresa_id, $role_user_id);
  public function totalVendasEstornadasAnoMesAtual($user_id, $empresa_id, $role_user_id);
  public function conversaoMensal($user_id, $empresa_id, $role_user_id);
  public function quantidadeContatosMes($user_id, $empresa_id, $role_user_id);
}
