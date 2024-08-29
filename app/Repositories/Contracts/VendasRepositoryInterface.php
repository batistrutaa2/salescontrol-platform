<?php

namespace App\Repositories\Contracts;

interface VendasRepositoryInterface
{
  public function  create(array $data);
  public function  all();
  public function vendasDoMesAnoAtual();
  public function totalVendasCadastradasAnoMesAtual();
  public function totalVendasImplantadasAnoMesAtual();
  public function totalVendasEstornadasAnoMesAtual();
  public function conversaoMensal();
  public function quantidadeContatosMes();
}
