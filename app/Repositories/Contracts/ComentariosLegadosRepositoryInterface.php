<?php

namespace App\Repositories\Contracts;

interface ComentariosLegadosRepositoryInterface
{
  public function getCommentsLegacy(string $cpf);
}
