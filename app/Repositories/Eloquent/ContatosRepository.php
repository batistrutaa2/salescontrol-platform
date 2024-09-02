<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ContatosRepository implements ContatosRepositoryInterface
{

  protected $model;

  public function __construct(Contatos $model)
  {
    $this->model = $model;
  }


  public function create(array $data)
  {
    return $this->model::create($data);
  }

  public function getNewlyImportedBase($idBase)
  {
    return $this->model->where('id_operacao', $idBase)->get();
  }


  public function all()
  {
    return $this->model->all();
  }

  public function find($id)
  {
    return $this->model->where('id', $id)->first();
  }

  public function searchForCpfsFound(array $cpfs)
  {
    $contatos = $this->model->whereIn('cpf', $cpfs)->where('empresa_id', Auth::user()->empresa_id)->get();

    $result = $contatos->map(function ($contato) {
      return [
        'id' => $contato->id,
        'nome' => $contato->nome_cliente,
        'telefone1' => $contato->telefone1,
        'telefone2' => $contato->telefone2,
        'telefone3' => $contato->telefone3,
        'cpf' => $contato->cpf,
      ];
    });

    return $result->toArray();
  }

  public function searchForCpfFound($cpf)
  {
    $contatos = $this->model->where('cpf', $cpf)->where('empresa_id', Auth::user()->empresa_id)->first();

    return $contatos;
  }



  public function updateContact($idMailing, $telefone1, $telefone2, $telefone3, $negotiationValue)
  {
    try {
      $contact = $this->find($idMailing);
      $contact->telefone1 =  Helpers::cleanSpecialCharacters($telefone1) ?? "";
      $contact->telefone2 =  Helpers::cleanSpecialCharacters($telefone2) ?? "";
      $contact->telefone3 =  Helpers::cleanSpecialCharacters($telefone3) ?? "";
      $contact->valor_negociacao = Helpers::formatCurrencyToDecimal($negotiationValue);
      $contact->save();
    } catch (\Throwable $th) {
      return false;
    }
  }


  public function updateOrCreate(array $data)
  {
    try {
      $serchClient = ["id" => $data['id']];
      if (Auth::user()->role->id === UserRole::ADMINISTRATIVO || Auth::user()->role->id === UserRole::DEVELOPER) {
        $dataClient = [
          'nome_cliente' => $data['nome_cliente'],
          'email' => $data['email'],
          'cpf' => Helpers::cleanSpecialCharacters($data['cpf']),
          'data_nascimento' => $data['data_nascimento'],
          'plano' => $data['plano'],
          'cartegoria' => $data['cartegoria'],
          'entidade' => $data['entidade'],
          'idades' => $data['idades'],
          'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1']),
          'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2']),
          'telefone3' => Helpers::cleanSpecialCharacters($data['telefone3']),
          'valor_plano_atual' => Helpers::formatCurrencyToDecimal($data['valor_plano_atual']),
          'valor_negociacao' => Helpers::formatCurrencyToDecimal($data['valor_negociacao'])
        ];
      } else {
        $dataClient = [
          'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1']),
          'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2']),
          'telefone3' => Helpers::cleanSpecialCharacters($data['telefone3']),
          'valor_negociacao' => Helpers::formatCurrencyToDecimal($data['valor_negociacao'])
        ];
      }
      $this->model::updateOrCreate($serchClient, $dataClient);
      return true;
    } catch (\Throwable $th) {
      return false;
    }
  }
}
