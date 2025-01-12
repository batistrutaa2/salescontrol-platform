<?php

namespace App\Http\Controllers\pages\backoffice;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Contracts\VendasRepositoryInterface;

class Backoffice extends Controller
{
  protected VendasRepository $vendasRepository;

  public function __construct(

    VendasRepositoryInterface $vendasRepositoryInterface,

  ) {

    $this->vendasRepository = $vendasRepositoryInterface;
  }


  public function index()
  {
    return view("content.pages.backoffice.index");
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
}
