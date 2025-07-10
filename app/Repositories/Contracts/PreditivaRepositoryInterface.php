<?php


namespace App\Repositories\Contracts;

interface PreditivaRepositoryInterface
{
  public function create(array $data);
  public function getNextCLient(string $empresa_id);

}
