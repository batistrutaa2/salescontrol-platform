<?php

namespace App\Repositories\Eloquent;

use App\Models\Contatos;
use App\Repositories\Contracts\ContatosRepositoryInterface;

class ContatosRepository implements ContatosRepositoryInterface
{

  protected $model;

  public function __construct(Contatos $model)
  {
    $this->model = $model;
  }


  public function create(array $data)
  {
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
    $contatos = $this->model->whereIn('cpf', $cpfs)->get();

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



  public function updateContact($idMailing, $telefone1, $telefone2, $telefone3)
  {
    try {
      $contact = $this->find($idMailing);
      $contact->telefone1 = $telefone1;
      $contact->telefone2 = $telefone2;
      $contact->telefone3 = $telefone3;
      return $contact->save();
    } catch (\Throwable $th) {
      dd("caiu aq");
      return false;
    }
  }
}
