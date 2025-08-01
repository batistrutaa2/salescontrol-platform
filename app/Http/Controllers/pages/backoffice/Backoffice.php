<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\Tabulations;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Modules\Ranking\Ranking;

class Backoffice extends Controller
{
  protected VendasRepository $vendasRepository;
  protected TabulacoesRepository $tabulacoesRepository;
  protected ContatosCorretoresRepository $contatosCorretoresRepository;


  public function __construct(

    VendasRepositoryInterface $vendasRepositoryInterface,
    TabulacoesRepositoryInterface $tabulacoesRepositoryInterface,
    ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface

  ) {
    $this->vendasRepository = $vendasRepositoryInterface;
    $this->tabulacoesRepository = $tabulacoesRepositoryInterface;
    $this->contatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
  }


  public function index()
  {
    $tabulations = $this->tabulacoesRepository->getTabulationsBackoffice(Auth::user()->empresa_id);
    return view("content.pages.backoffice.index", [
      'tabulacoes' => $tabulations
    ]);
  }

  public function listContract()
  {
    $sales = $this->vendasRepository->all(Auth::user()->empresa_id);

    return response()->json([
      'data' => $sales
    ]);
  }

  public function listSalesFilter(Request $request)
  {
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    if (!$startDate && !$endDate) {
      $vendas = $this->vendasRepository->all(Auth::user()->empresa_id);
    } else {
      $vendas = $this->vendasRepository->getSalesFilter($startDate, $endDate, Auth::user()->empresa_id);
    }

    return response()->json([
      'data' => $vendas,
    ]);
  }

  public function openContract(string $idContract)
  {
    $sale = $this->vendasRepository->find($idContract);
    return view("content.pages.backoffice.openContract", [
      'contract' => $sale
    ]);
  }

  public function updateSale(Request $request)
  {
    if ($this->vendasRepository->updateContract($request->all())) {
      return redirect()->route(route: 'backoffice.index')->with('status', 'success')->with('message', "Contrato Atualizado");
    } else {
      return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', "Erro ao atualizar contrato ,contate nosso suporte");
    }
  }

  public function alterStatusContract(Request $request)
  {
    $sale = $this->vendasRepository->find($request->idSale);
    $updateContract = $this->contatosCorretoresRepository->alterStatusContract($sale->contato_id, $request->tabulacao_id);
    
    if($request->tabulacao_id == Tabulations::IMPLANTADO) {
      $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, now());
    }

    if ($updateContract) {
      return redirect()->route(route: 'backoffice.index')->with('status', 'success')->with('message', "Contrato Atualizado");
    } else {
      return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', "Erro ao atualizar contrato ,contate nosso suporte");
    }
  }


  public function deleteContract($id)
  {
    $deleteContract = $this->vendasRepository->delete($id);
    if ($deleteContract) {
      return redirect()->route(route: 'backoffice.index')->with('status', 'success')->with('message', "Contrato Deletado com sucesso");
    } else {
      return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', "Erro ao Deletar contrato ,contate nosso suporte");
    }
  }

}
