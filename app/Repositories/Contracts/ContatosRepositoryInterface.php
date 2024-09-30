<?php

namespace App\Repositories\Contracts;

interface ContatosRepositoryInterface
{
  public function create(array $data);
  public function all();
  public function find($id);
  public function searchForCpfsFound(array $cpfs);
  public function searchForCpfFound($cpf);
  public function updateContact($idMailing, $telefone1, $telefone2, $telefone3, $valor_negociacao);
  public function updateOrCreate(array $data);
  public function getLeads($empresa_id);
  public function quantidadeContatosImportadosMes($month, $year, $empresa_id);
}
