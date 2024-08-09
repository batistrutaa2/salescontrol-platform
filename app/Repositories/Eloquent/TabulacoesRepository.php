<?php

namespace App\Repositories\Eloquent;

use App\Models\Tabulacoes;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;

class TabulacoesRepository implements TabulacoesRepositoryInterface
{

  protected $model;

  public function __construct(Tabulacoes $model)
  {
    $this->model = $model;
  }

  public function getTabulationsCompanieCommercial($empresa_id)
  {
    return $this->model->select(['id', 'descricao', 'ordem_kanban'])->where('empresa_id', $empresa_id)->where('status', 'Y')->where('tipo_tabulacao', 'C')->get();
  }
}
