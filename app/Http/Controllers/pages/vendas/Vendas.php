<?php

namespace App\Http\Controllers\pages\vendas;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\VendasRepository;

class Vendas extends Controller
{

  protected VendasRepository $repositoryVendas;
  public function __construct(
    VendasRepositoryInterface $vendasRepositoryInterface
  ) {

    $this->repositoryVendas = $vendasRepositoryInterface;
  }

  public function index()
  {
    return view('content.pages.vendas.index');
  }


  public function salesOfTheMonth()
  {
    $vendas = $this->repositoryVendas->vendasDoMesAnoAtual();
    return response()->json(['data' => $vendas]);
  }
}
