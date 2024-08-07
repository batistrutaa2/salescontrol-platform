<?php

namespace App\Repositories\Contracts;

interface ComentariosLegadosRepositoryInterface
{
  public function getCommentsLegacy(string $cpf, string $telefone1, string $telefone2, string $telefone3);
}
