<?php

namespace App\Repositories\Eloquent;

use App\Models\ComentariosLegado;
use App\Repositories\Contracts\ComentariosLegadosRepositoryInterface;

class ComentariosLegadosRepository implements ComentariosLegadosRepositoryInterface
{

  protected $model;

  public function __construct(ComentariosLegado $model)
  {
    $this->model = $model;
  }

  public function getCommentsLegacy(string $cpf, string $telefone1, string $telefone2, string $telefone3)
  {
    $query = $this->model::query();

    $query->where(function ($query) use ($cpf, $telefone1, $telefone2, $telefone3) {
      if ($cpf) {
        $query->orWhere('cpf', $cpf);
      }
      if ($telefone1) {
        $query->orWhere('telefone1', $telefone1);
      }
      if ($telefone2) {
        $query->orWhere('telefone2', $telefone2);
      }
      if ($telefone3) {
        $query->orWhere('telefone3', $telefone3);
      }
    });

    return $query->get();
  }
}
