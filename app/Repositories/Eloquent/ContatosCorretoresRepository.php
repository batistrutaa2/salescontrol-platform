<?php

namespace App\Repositories\Eloquent;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\ContatosCorretores;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContatosCorretoresRepository implements ContatosCorretoresRepositoryInterface
{

  protected $model;

  public function __construct(ContatosCorretores $model)
  {
    $this->model = $model;
  }

  public function getClientComercial(string $rulerUser, string $empresa_id)
  {
    if ($rulerUser == UserRole::ADMINISTRATIVO || $rulerUser == UserRole::BACKOFFICE) {
      return $this->model::select(
        'tabulacoes.id',
        'tabulacoes.descricao as title',
        'contatos.id as idContato',
        'tabulacoes.ordem_kanban',
        'contatos.nome_cliente',
        DB::raw("IF(data_nascimento LIKE '%/%', data_nascimento, FROM_UNIXTIME((data_nascimento - 25569) * 86400, '%d/%m/%Y')) as data_nascimento"),
        'contatos.cpf',
        'contatos.plano',
        'contatos.categoria',
        'contatos.entidade',
        'contatos.telefone1',
        'contatos.telefone2',
        'contatos.telefone3',
        'contatos.email',
        'contatos.idades',
        'contatos.valor_plano_atual',
        'contatos.valor_negociacao',
        'contatos_corretores.temperatura',
        'contatos_corretores.user_id',
        'contatos_corretores.updated_at',
        'contatos_corretores.created_at',
        'users.name as nameVendedor',
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->leftJoin('users', 'users.id', '=', 'contatos_corretores.user_id')
        ->where('contatos_corretores.empresa_id', $empresa_id)
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura', 'contatos_corretores.user_id', 'contatos_corretores.updated_at', 'tabulacoes.ordem_kanban', 'contatos_corretores.created_at', 'nameVendedor')
        ->orderBy('contatos.created_at', 'desc')
        ->get();
    } elseif ($rulerUser == UserRole::DEVELOPER) {
      return $this->model::select(
        'tabulacoes.id',
        'tabulacoes.descricao as title',
        'tabulacoes.ordem_kanban',
        'contatos.id as idContato',
        'contatos.nome_cliente',
        DB::raw("IF(data_nascimento LIKE '%/%', data_nascimento, FROM_UNIXTIME((data_nascimento - 25569) * 86400, '%d/%m/%Y')) as data_nascimento"),
        'contatos.cpf',
        'contatos.plano',
        'contatos.categoria',
        'contatos.entidade',
        'contatos.telefone1',
        'contatos.telefone2',
        'contatos.telefone3',
        'contatos.email',
        'contatos.idades',
        'contatos.valor_plano_atual',
        'contatos.valor_negociacao',
        'contatos_corretores.temperatura',
        'contatos_corretores.user_id',
        'contatos_corretores.updated_at',
        'contatos_corretores.created_at',
        'users.name as nameVendedor',
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->leftJoin('users', 'users.id', '=', 'contatos_corretores.user_id')
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura', 'contatos_corretores.user_id', 'contatos_corretores.updated_at', 'tabulacoes.ordem_kanban', 'contatos_corretores.created_at', 'nameVendedor')
        ->orderBy('contatos.created_at', 'desc')
        ->get();
    } elseif ($rulerUser == UserRole::VENDEDOR) {
      return $this->model::select(
        'tabulacoes.id',
        'tabulacoes.descricao as title',
        'tabulacoes.ordem_kanban',
        'contatos.id as idContato',
        'contatos.nome_cliente',
        DB::raw("IF(data_nascimento LIKE '%/%', data_nascimento, FROM_UNIXTIME((data_nascimento - 25569) * 86400, '%d/%m/%Y')) as data_nascimento"),
        'contatos.cpf',
        'contatos.plano',
        'contatos.categoria',
        'contatos.entidade',
        'contatos.telefone1',
        'contatos.telefone2',
        'contatos.telefone3',
        'contatos.email',
        'contatos.idades',
        'contatos.valor_plano_atual',
        'contatos.valor_negociacao',
        'contatos_corretores.temperatura',
        'contatos_corretores.user_id',
        'contatos_corretores.updated_at',
        'contatos_corretores.created_at',
        'users.name as nameVendedor',
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->leftJoin('users', 'users.id', '=', 'contatos_corretores.user_id')
        ->where('contatos_corretores.user_id', Auth::user()->id)
        ->where('contatos_corretores.empresa_id', $empresa_id)
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura', 'contatos_corretores.user_id', 'contatos_corretores.updated_at', 'tabulacoes.ordem_kanban', 'contatos_corretores.created_at', 'nameVendedor')
        ->orderBy('contatos.created_at', 'desc')
        ->get();
    }
  }


  public function changeStatusLead($data): bool
  {
    try {
      DB::beginTransaction();
      $card = $this->model->where('contato_id', $data['contato_id'])->first();
      if ($card) {
        if ($data['tabulacao_id'] == Tabulations::NEGOCIO_NAO_FECHADO) {
          $card->tabulacao_id = Tabulations::REMARKETING;
        } else {
          $card->tabulacao_id = $data['tabulacao_id'];
        }
        $save = $card->save();
        DB::commit();
        return $save;
      } else {
        DB::rollBack();
        return false;
      }
    } catch (\Throwable $th) {
      DB::rollBack();
      return false;
    }
  }


  public function updateLeadTemperature($idMailing, $temperatura)
  {
    try {
      $contactRelationship = $this->model->where('contato_id', $idMailing)->first();
      $contactRelationship->temperatura = $temperatura;
      return $contactRelationship->save();
    } catch (\Throwable $th) {
      return false;
    }
  }

  public function getClientInfo($idMailing)
  {

    $client = $this->model->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
      ->where('contatos_corretores.contato_id', $idMailing)
      ->select(
        'contatos.id',
        'contatos.nome_cliente',
        'contatos.email',
        'contatos.cpf',
        DB::raw("IF(data_nascimento LIKE '%/%', data_nascimento, FROM_UNIXTIME((data_nascimento - 25569) * 86400, '%d/%m/%Y')) as data_nascimento"),
        'contatos_corretores.temperatura',
        'contatos.plano',
        'contatos.categoria',
        'contatos.entidade',
        'contatos.telefone1',
        'contatos.telefone2',
        'contatos.telefone3',
        'contatos.idades',
        'contatos.valor_plano_atual',
        'contatos.valor_negociacao'
      )
      ->get();

    return $client[0];
  }

  public function updateTemperatureAndTabulation(string $temperature, string $idMailing, string $tabulacao_id)
  {
    try {
      $searchRegister = [
        'contato_id' => $idMailing
      ];

      $this->model::updateOrCreate($searchRegister, ['temperatura' => $temperature, 'tabulacao_id' => $tabulacao_id]);
      return true;
    } catch (\Throwable $th) {
      return false;
    }
  }

  public function getRemarketingLeads(string $empresa_id)
  {
    $results = $this->model::leftJoin('contatos as b', 'contatos_corretores.contato_id', '=', 'b.id')
      ->select('b.id', 'b.nome_cliente', 'b.email', 'b.telefone1', 'contatos_corretores.updated_at')
      ->where('contatos_corretores.empresa_id', $empresa_id)
      ->where('contatos_corretores.tabulacao_id', Tabulations::REMARKETING)
      ->get();

    return $results;
  }

  public function getTabulationId($idMailing)
  {
    return $this->model->select('tabulacao_id')->where('contato_id', $idMailing)->first();
  }

  public function transferContact(array $data)
  {
    try {
      $lead = $this->model::where('contato_id', $data['idMailing'])->first();
      $lead->user_id = $data['user_id'];
      $lead->tabulacao_id = $data['tabulation_id'];
      $lead->created_at = Carbon::now();
      $lead->updated_at = Carbon::now();
      return $lead->save();
    } catch (\Throwable $th) {
      return false;
    }
  }


  public function transferContactInNulk(array $data)
  {
    try {
      DB::beginTransaction();
      $leadIds = explode(',', $data['selectedLeadIds']);
      array_map(function ($leadId) use ($data) {
        $this->model->where('contato_id', $leadId)->update([
          'user_id' => $data['user_id'],
          'tabulacao_id' => $data['tabulation_id'],
          'created_at' => Carbon::now(),
          'updated_at' => Carbon::now(),
        ]);
      }, $leadIds);
      DB::commit();
      return true;
    } catch (\Throwable $th) {
      dd($th);
      DB::rollBack();
      return false;
    }
  }
}
