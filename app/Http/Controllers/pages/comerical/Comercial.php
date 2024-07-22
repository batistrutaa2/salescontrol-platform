<?php

namespace App\Http\Controllers\pages\comerical;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Comercial extends Controller
{
  protected ContatosCorretoresRepository  $repositoryContatosCorretoresRepository;
  protected TabulacoesRepositoryInterface  $tabulacoesRepository;

  public function __construct(
    ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface,
    TabulacoesRepositoryInterface $TabulacoesRepositoryInterface
  ) {
    $this->repositoryContatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
    $this->tabulacoesRepository = $TabulacoesRepositoryInterface;
  }

  public function index()
  {
    return view('content.pages.comercial.index');
  }

  public function getClientComercial()
  {
    $contacts = $this->repositoryContatosCorretoresRepository->getClientComercial(auth()->user()->user_role_id, auth()->user()->empresa_id);
    $structuredData = $this->structureBoardData($contacts);

    return response()->json($structuredData);
  }

  protected function structureBoardData($contacts)
  {
    $status = $this->tabulacoesRepository->getTabulationsCompanie(Auth::user()->empresa_id);

    $boardData = [];

    foreach ($status as $tabulation) {

      $items = $contacts->filter(function ($contact) use ($tabulation) {
        return $contact->id == $tabulation['id'];
      })->map(function ($contact) {
        return [
          'id' =>  $contact->idContato,
          'title' => $contact->nome_cliente,
          'comments' => (string) $contact->qt_comentarios,
          'badge-text' => 'TBD',
          'badge' => 'success',
          'due-date' => 'TBD',
          'attachments' => 'TBD',
        ];
      })->values()->toArray();

      $boardData[] = [
        'id' =>  Helpers::normalizeStatusName($tabulation['id']),
        'title' => $tabulation['descricao'],
        'item' => $items
      ];
    }

    return $boardData;
  }

  public function changeStatusLead(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'contato_id' => 'required|integer',
      'tabulacao_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => 'Invalid data'], 400);
    }

    $saveStatus = $this->repositoryContatosCorretoresRepository->changeStatusLead($request->all());

    if ($saveStatus) {
      return response()->json(
        [
          'error' => false,
          'message' => 'Status atualizado com sucesso.'
        ],
        200
      );
    } else {
      return response()->json(
        [
          'error' => true,
          'message' => 'Erro ao atualizar status.'
        ],
        501
      );
    }
  }
}
