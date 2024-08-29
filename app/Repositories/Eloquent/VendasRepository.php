<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Vendas;
use App\Repositories\Contracts\VendasRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

  public function vendasDoMesAnoAtual()
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
      return DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'b.tabulacao_id as status', 'a.created_at')
        ->where('a.user_id', Auth::user()->id)
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.data_vigencia', $currentMonth)
        ->whereYear('a.data_vigencia', $currentYear)
        ->get();
    } else {
      return DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'b.tabulacao_id as status', 'a.created_at')
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.data_vigencia', $currentMonth)
        ->whereYear('a.data_vigencia', $currentYear)
        ->get();
    }
  }
}
