<?php

namespace App\Http\Controllers\pages;


use App\Enums\UserRole;
use App\Repositories\Contracts\TransferenciaContatoRepositoryInterface;
use App\Repositories\Eloquent\TransferenciaContatoRepository;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;

class HomePage extends Controller
{

  protected $vendasRepository;
  protected $contatosRepository;
  protected TransferenciaContatoRepository $transferenciaContatoRepository;

  public function __construct(
    VendasRepositoryInterface $vendasRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface,
    TransferenciaContatoRepositoryInterface $transferenciaContatoRepositoryInterface
  ) {
    $this->vendasRepository = $vendasRepositoryInterface;
    $this->contatosRepository = $contatosRepositoryInterface;
    $this->transferenciaContatoRepository = $transferenciaContatoRepositoryInterface;
  }

  public function index()
  {
    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {
      return redirect()->route('comercial.kanban');
    }


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
    $contatosTransferidos = $this->transferenciaContatoRepository->monthlyTransferCount($month, $year, $empresaId);
    $conversaoMensal = $this->vendasRepository->conversaoMensalPorData($empresaId, $month, $year);
    $contratosCadastrados = $this->vendasRepository->listaVendasCadastradasMes($month, $year, $empresaId);
    $contratosImplantados = $this->vendasRepository->listaVendasImplantadasMes($month, $year, $empresaId);

    return response()->json([
      'vendasCadastradasPorVendedor' => $vendasCadastradasPorVendedor,
      'vendasImplantadasPorVendedor' => $vendasImplantadasPorVendedor,
      'quantidadeContatosImportados' => $quantidadeContatosImportados,
      'quantidadeContatosTransferidos' => $contatosTransferidos,
      'conversaoMensal' => $conversaoMensal,
      'contratosCadastrados' => $contratosCadastrados,
      'contratosImplantados' => $contratosImplantados,
      'contatosImportadosMesPorVendedor' => $contatosImportadosMesPorVendedor
    ]);
  }

}
