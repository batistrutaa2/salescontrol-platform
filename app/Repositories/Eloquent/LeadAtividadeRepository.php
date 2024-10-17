<?php

namespace App\Repositories\Eloquent;

use App\Models\Empresa;
use App\Models\LeadAtividade;
use App\Enums\AtividadesLeads;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\LeadAtividadeRepositoryInterface;

class LeadAtividadeRepository implements LeadAtividadeRepositoryInterface
{

  protected $model;

  public function __construct(LeadAtividade $model)
  {
    $this->model = $model;
  }

  public function create(array $data)
  {
    try {
      $this->model::create([
        'empresa_id' => Auth::user()->empresa_id,
        'user_id' => Auth::user()->id,
        'contato_id' => $data['id_mailing'],
        'tabulacao_anterior_id' => $data['id_tabulacao'],
        'tabulacao_atual_id' => $data['id_tabulacao'],
        'log_descricao' => AtividadesLeads::ATIVIDADE_COMENTARIO,
      ]);
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
