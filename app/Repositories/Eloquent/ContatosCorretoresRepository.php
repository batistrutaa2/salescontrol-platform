<?php

namespace App\Repositories\Eloquent;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\ContatosCorretores;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
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
        'contatos.nome_cliente',
        'contatos.data_nascimento',
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
        'contatos_corretores.temperatura',
        'contatos_corretores.user_id',
        'contatos_corretores.updated_at',
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->where('contatos_corretores.user_id', 1)
        ->where('contatos_corretores.empresa_id', $empresa_id)
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura', 'contatos_corretores.user_id', 'contatos_corretores.updated_at')
        ->orderBy('contatos.created_at', 'desc')
        ->get();
    } elseif ($rulerUser == UserRole::DEVELOPER) {
      return $this->model::select(
        'tabulacoes.id',
        'tabulacoes.descricao as title',
        'contatos.id as idContato',
        'contatos.nome_cliente',
        'contatos.data_nascimento',
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
        'contatos_corretores.temperatura',
        'contatos_corretores.user_id',
        'contatos_corretores.updated_at',
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura', 'contatos_corretores.user_id', 'contatos_corretores.updated_at')
        ->orderBy('contatos.created_at', 'desc')
        ->get();
    } elseif ($rulerUser == UserRole::VENDEDOR) {
      return $this->model::select(
        'tabulacoes.id',
        'tabulacoes.descricao as title',
        'contatos.id as idContato',
        'contatos.nome_cliente',
        'contatos.data_nascimento',
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
        'contatos_corretores.temperatura',
        'contatos_corretores.user_id',
        'contatos_corretores.updated_at',
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->where('contatos_corretores.user_id', auth()->user()->id)
        ->where('contatos_corretores.empresa_id', $empresa_id)
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura', 'contatos_corretores.user_id', 'contatos_corretores.updated_at')
        ->orderBy('contatos.created_at', 'desc')
        ->get();
    }
  }


  public function changeStatusLead($data): bool
  {
    try {
      $card = $this->model->where('contato_id', $data['contato_id'])->first();

      if ($card) {
        // enviando pra fila de remarketing
        if ($data['tabulacao_id'] == 6) {
          $card->tabulacao_id = 10;
          $card->user_id = null;
        } else {
          $card->tabulacao_id = $data['tabulacao_id'];
        }
        return $card->save();
      } else {
        return false;
      }
    } catch (\Throwable $th) {
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
        'contatos.data_nascimento',
        'contatos_corretores.temperatura',
        'contatos.plano',
        'contatos.categoria',
        'contatos.entidade',
        'contatos.telefone1',
        'contatos.telefone2',
        'contatos.telefone3',
        'contatos.valor_plano_atual'
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
}
