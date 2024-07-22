<?php

namespace App\Http\Controllers\pages\comerical;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use Illuminate\Support\Facades\Auth;

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
    // Mapeando status
    $status = $this->tabulacoesRepository->getTabulationsCompanie(Auth::user()->empresa_id);

    $boardData = [];

    foreach ($status as $tabulation) {

      $items = $contacts->filter(function ($contact) use ($tabulation) {
        return $contact->id == $tabulation['id'];
      })->map(function ($contact) {
        return [
          'id' => 'contact-' . $contact->idContato,
          'title' => $contact->nome_cliente,
          'comments' => (string) $contact->qt_comentarios,
          'badge-text' => 'TBD',
          'badge' => 'success',
          'due-date' => 'TBD',
          'attachments' => 'TBD',
        ];
      })->values()->toArray();

      $boardData[] = [
        'id' => 'board-' . Helpers::normalizeStatusName($tabulation['descricao']),
        'title' => $tabulation['descricao'],
        'item' => $items
      ];
    }

    return $boardData;
  }
}
