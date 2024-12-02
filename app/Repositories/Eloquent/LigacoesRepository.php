<?php

namespace App\Repositories\Eloquent;

use App\Models\Ligacoes;
use App\Enums\AtividadesLeads;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contracts\LigacoesRepositoryInterface;

class LigacoesRepository implements LigacoesRepositoryInterface
{
  protected $model;

  public function __construct(Ligacoes $model)
  {
    $this->model = $model;
  }

  public function create(array $data)
  {
    try {
      $this->model::create([
        'empresa_id' => $data['empresa_id'],
        'user_id' => $data['user_id'],
        'contato_id' => $data['contato_id'],
        'telefone' => $data['telefone'],
        'tabulacao_id' => $data['status'],
        'id_call' => $data['id_call']
      ]);
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
