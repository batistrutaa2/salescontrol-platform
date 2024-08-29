<?php

namespace App\Repositories\Eloquent;

use App\Enums\Tabulations;
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
      DB::beginTransaction();
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
      DB::commit();
      return true;
    } catch (Exception $ex) {
      DB::rollBack();
      return false;
    }
  }

  public function  all() {}

  public function vendasDoMesAnoAtual()
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {

      return  DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'b.tabulacao_id as status', 'a.created_at')
        ->where('a.user_id', Auth::user()->id)
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();
    } else {
      return DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'b.tabulacao_id as status', 'a.created_at')
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();
    }
  }


  public function totalVendasCadastradasAnoMesAtual()
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.user_id', Auth::user()->id)
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->where('b.tabulacao_id', Tabulations::VENDA)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    } else {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->where('b.tabulacao_id', Tabulations::VENDA)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    }
  }


  public function totalVendasImplantadasAnoMesAtual()
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.user_id', Auth::user()->id)
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->where('b.tabulacao_id', Tabulations::IMPLANTADO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    } else {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->where('b.tabulacao_id', Tabulations::IMPLANTADO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    }
  }

  public function totalVendasEstornadasAnoMesAtual()
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
      $estornos = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_estornado'),
          DB::raw('COUNT(a.id) as quantidade_estornada')
        )
        ->where('a.user_id', Auth::user()->id)
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->where('b.tabulacao_id', Tabulations::ESTORNO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_estornado' => $estornos[0]->valor_estornado ?? 0.0,
        'quantidade_estornada' => $estornos[0]->quantidade_estornada ?? 0.0,
      ];
    } else {
      $estornos = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_estornado'),
          DB::raw('COUNT(a.id) as quantidade_estornada')
        )
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->where('b.tabulacao_id', Tabulations::ESTORNO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_estornado' => $estornos[0]->valor_estornado ?? 0.0,
        'quantidade_estornada' => $estornos[0]->quantidade_estornada ?? 0.0,
      ];
    }
  }


  public function conversaoMensal()
  {
    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
      $quantidadeVendasMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where(function ($query) {
          $query->where('b.tabulacao_id', Tabulations::VENDA)
            ->orWhere('b.tabulacao_id', Tabulations::IMPLANTADO);
        })
        ->where('b.user_id', Auth::user()->id)
        ->where('b.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();

      $quantidadeContatosMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.user_id', Auth::user()->id)
        ->where('b.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
      return   $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);
    } else {
      $quantidadeVendasMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where(function ($query) {
          $query->where('b.tabulacao_id', Tabulations::VENDA)
            ->orWhere('b.tabulacao_id', Tabulations::IMPLANTADO);
        })
        ->where('b.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();

      $quantidadeContatosMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
      return   $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);
    }
  }



  private function calculoConversao($quantidadeContatos, $quantidadeVendas)
  {
    if ($quantidadeVendas == 0) {
      return 0;
    }

    $conversao = ($quantidadeVendas / $quantidadeContatos) * 100;

    return number_format($conversao, 2, ',', '.');
  }


  public function quantidadeContatosMes()
  {
    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
      return DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.user_id', Auth::user()->id)
        ->where('b.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
    } else {
      return DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
    }
  }
}
