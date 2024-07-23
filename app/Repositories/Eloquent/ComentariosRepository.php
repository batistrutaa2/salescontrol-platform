<?php

namespace App\Repositories\Eloquent;

use App\Models\Comentarios;
use App\Repositories\Contracts\ComentariosRepositoryInterface;

class ComentariosRepository implements ComentariosRepositoryInterface
{

  protected $model;

  public function __construct(Comentarios $model)
  {
    $this->model = $model;
  }
}
