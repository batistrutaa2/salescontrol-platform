<?php

namespace App\Repositories\Eloquent;

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
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->where('contatos_corretores.user_id', 1)
        ->where('contatos_corretores.empresa_id', $empresa_id)
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura')
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
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura')
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
        DB::raw('COUNT(comentarios.id) as qt_comentarios')
      )
        ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->leftJoin('comentarios', 'comentarios.contato_id', '=', 'contatos.id')
        ->where('contatos_corretores.user_id', auth()->user()->id)
        ->where('contatos_corretores.empresa_id', $empresa_id)
        ->groupBy('tabulacoes.id', 'tabulacoes.descricao', 'contatos.id', 'contatos.nome_cliente', 'contatos_corretores.temperatura')
        ->orderBy('contatos.created_at', 'desc')
        ->get();
    }
  }


  public function changeStatusLead($data): bool
  {
    try {
      $card = $this->model->where('contato_id', $data['contato_id'])->first();

      if ($card) {
        $card->tabulacao_id = $data['tabulacao_id'];
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

  public function updateTemperature(string $temperature, string $idMailing)
  {
    try {
      $searchRegister = [
        'contato_id' => $idMailing
      ];
      $this->model::updateOrCreate($searchRegister, ['temperatura' => $temperature]);
      return true;
    } catch (\Throwable $th) {
      return false;
    }
  }
}
