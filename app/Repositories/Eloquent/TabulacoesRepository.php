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

  public function getAll($empresa_id)
  {
    return $this->model->select(['id', 'descricao'])->where('empresa_id', $empresa_id)->where('status', 'Y')->get();
  }

  public function getTabulationsBackoffice($empresa_id)
  {
    return $this->model->select(['id', 'descricao'])
      ->where('empresa_id', $empresa_id)
      ->where('status', 'Y')
      ->where('tipo_tabulacao', "A")
      ->whereIn('descricao', ['VENDA', 'ESTORNO', 'IMPLANTADO', 'DECLINADO'])
      ->get();
  }


  public function getSubTabulations($empresa_id)
  {
    return $this->model->select(['id', 'descricao'])
      ->where('empresa_id', $empresa_id)
      ->where('status', 'Y')
      ->where('sub_tabulacao', 'S')
      ->get();
  }

}
