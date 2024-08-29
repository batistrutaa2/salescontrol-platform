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
    $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual();
    $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual();
    $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual();
    $percentualConversaoMes = $this->repositoryVendas->conversaoMensal();
    $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes();

    return view('content.pages.vendas.index', [
      'vendasCadastradasMes' => $vendasCadastradasMes,
      'vendasImplantadasMes' => $vendasImplantadasMes,
      'vendasEstornadasMes' => $vendasEstornadasMes,
      'percentualConversaoMes' => $percentualConversaoMes,
      'totalContatosMes' => $totalContatosMes
    ]);
  }


  public function salesOfTheMonth()
  {
    $vendas = $this->repositoryVendas->vendasDoMesAnoAtual();
    return response()->json(['data' => $vendas]);
  }
}
