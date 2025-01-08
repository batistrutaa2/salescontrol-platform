<?php

namespace App\Http\Controllers\pages\vendas;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Eloquent\VendasRepository;
use Illuminate\Support\Facades\Auth;

class Vendas extends Controller
{

  protected VendasRepository $repositoryVendas;
  protected UsuariosRepository $usuariosRepository;
  public function __construct(
    VendasRepositoryInterface $vendasRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface
  ) {

    $this->repositoryVendas = $vendasRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
  }

  public function index()
  {
    $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
    $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
    $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
    $percentualConversaoMes = $this->repositoryVendas->conversaoMensal(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
    $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);

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
    $vendas = $this->repositoryVendas->vendasDoMesAnoAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
    return response()->json(['data' => $vendas]);
  }


  public function monthlySalesFilter($name_user = null)
  {
    try {

      if (is_null($name_user)) {
        $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
        $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
        $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
        $percentualConversaoMes = $this->repositoryVendas->conversaoMensal(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
        $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes(Auth::user()->id, Auth::user()->empresa_id, Auth::user()->user_role_id);
      } else {
        $user = $this->usuariosRepository->getUserSearchName($name_user);

        if (is_null($user)) {
          return response()->json(["error" => true]);
        }

        $vendasCadastradasMes = $this->repositoryVendas->totalVendasCadastradasAnoMesAtual($user->id, $user->empresa_id, $user->user_role_id);
        $vendasImplantadasMes = $this->repositoryVendas->totalVendasImplantadasAnoMesAtual($user->id, $user->empresa_id, $user->user_role_id);
        $vendasEstornadasMes = $this->repositoryVendas->totalVendasEstornadasAnoMesAtual($user->id, $user->empresa_id, $user->user_role_id);
        $percentualConversaoMes = $this->repositoryVendas->conversaoMensal($user->id, $user->empresa_id, $user->user_role_id);
        $totalContatosMes = $this->repositoryVendas->quantidadeContatosMes($user->id, $user->empresa_id, $user->user_role_id);
      }

      $response = [
        'vendasCadastradasMes' => $vendasCadastradasMes,
        'vendasImplantadasMes' => $vendasImplantadasMes,
        'vendasEstornadasMes' => $vendasEstornadasMes,
        'percentualConversaoMes' => $percentualConversaoMes,
        'totalContatosMes' => $totalContatosMes,
        "error" => false
      ];

      return response()->json($response);
    } catch (\Throwable $th) {
      return response()->json(["error" => true]);
    }
  }

  public function analyticalSales()
  {
    return view('content.pages.vendas.analyticalSales');
  }
}
