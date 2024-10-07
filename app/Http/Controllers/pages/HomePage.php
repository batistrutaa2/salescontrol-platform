<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use Illuminate\Support\Carbon;

class HomePage extends Controller
{

  protected $vendasRepository;
  protected $contatosRepository;

  public function __construct(
    VendasRepositoryInterface $vendasRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface
  ) {
    $this->vendasRepository = $vendasRepositoryInterface;
    $this->contatosRepository = $contatosRepositoryInterface;
  }

  public function index()
  {
    return view('content.pages.pages-home');
  }


  public function searchMetrics($month, $year)
  {
    $month = $month ?? Carbon::now()->month;
    $year = $year ?? Carbon::now()->year;
    $empresaId = auth()->user()->empresa_id;

    $vendasCadastradasPorVendedor = $this->vendasRepository->quantidadeVendasCadastradasPorVendedor($month, $year, $empresaId);
    $vendasImplantadasPorVendedor = $this->vendasRepository->quantidadeVendasImplantadasVendedor($month, $year, $empresaId);
    $quantidadeContatosImportados = $this->contatosRepository->quantidadeContatosImportadosMes($month, $year, $empresaId);
    $contatosImportadosMesPorVendedor = $this->contatosRepository->quantidadeContatosImportadosMesPorVendedor($month, $year, $empresaId);
    $conversaoMensal = $this->vendasRepository->conversaoMensalPorData($empresaId, $month, $year);
    $contratosCadastrados = $this->vendasRepository->listaVendasCadastradasMes($month, $year, $empresaId);
    $contratosImplantados = $this->vendasRepository->listaVendasImplantadasMes($month, $year, $empresaId);

    return response()->json([
      'vendasCadastradasPorVendedor' => $vendasCadastradasPorVendedor,
      'vendasImplantadasPorVendedor' => $vendasImplantadasPorVendedor,
      'quantidadeContatosImportados' => $quantidadeContatosImportados,
      'conversaoMensal' => $conversaoMensal,
      'contratosCadastrados' => $contratosCadastrados,
      'contratosImplantados' => $contratosImplantados,
      'contatosImportadosMesPorVendedor' => $contatosImportadosMesPorVendedor
    ]);
  }

}
