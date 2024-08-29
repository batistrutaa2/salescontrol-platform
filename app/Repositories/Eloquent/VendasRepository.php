<?php

namespace App\Repositories\Eloquent;

use App\Helpers\Helpers;
use App\Models\Vendas;
use App\Repositories\Contracts\VendasRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Auth;

class VendasRepository implements VendasRepositoryInterface
{
  protected $model;

  public function __construct(Vendas $model)
  {
    $this->model = $model;
  }


  public function  create(array $data)
  {
    try {
      $this->model->create([
        'empresa_id' => Auth::user()->empresa_id,
        'user_id' => Auth::user()->id,
        'contato_id' => $data['contato_id'],
        'nome_contrato' => strtoupper($data['nome_contrato']),
        'cpf_cnpj' =>  Helpers::cleanSpecialCharacters($data['cpf_cnpj']),
        'email' => $data['email'],
        'data_vigencia' => $data['data_vigencia'],
        'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1']),
        'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2']),
        'operadora' =>  strtoupper($data['operadora']),
        'nome_plano' => strtoupper($data['nome_plano']),
        'valor_contrato' => Helpers::converterParaDecimal($data['valor_contrato']),
        'obs_contrato' => $data['obs_contrato'],
      ]);
      return true;
    } catch (Exception $ex) {
      dd($ex->getMessage());
      return false;
    }
  }
  public function  all() {}
}
