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

  public function getCommentsLegacy(string $cpf)
  {
    return $this->model->where('cpf', $cpf)->whereNotNull('anotacao')->orderBy('created_at', 'desc')->get();
  }
}
