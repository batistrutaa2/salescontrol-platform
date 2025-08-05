<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\Tabulations;
use App\Models\Operadora;
use App\Models\Plano;
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
use Illuminate\Support\Facades\Storage;

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
    $plano = Plano::find($sale->plano_id);
    $operadoras = Operadora::where('empresa_id', Auth::user()->empresa_id)->get();
    return view("content.pages.backoffice.openContract", [
      'contract' => $sale,
      'operadoras' => $operadoras,
      'plano' => $plano
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

    if ($request->tabulacao_id == Tabulations::IMPLANTADO) {
      $request->validate([
        'comprovante' => 'required|file|mimes:jpeg,jpg,png,pdf'
      ]);

      $file = $request->file('comprovante');
      $directory = "comprovantes/" . $sale->empresa_id . '/' . $sale->id;
      $fileName = 'comprovante_pagamento.' . $file->getClientOriginalExtension();
      Storage::putFileAs($directory, $file, $fileName);

      $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, now());
    }

    if ($updateContract) {
      return redirect()->route(route: 'backoffice.index')->with('status', 'success')->with('message', "Contrato Atualizado");
    } else {
      return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', "Erro ao atualizar contrato ,contate nosso suporte");
    }
  }

  public function downloadPaymentProof($id)
  {
    $sale = $this->vendasRepository->find($id);

    if (!$sale || !$sale->contatoCorretor || $sale->contatoCorretor->tabulacao_id != Tabulations::IMPLANTADO) {
      abort(404);
    }

    $directory = "comprovantes/" . $sale->empresa_id . '/' . $sale->id;
    $files = Storage::files($directory);

    if (empty($files)) {
      abort(404);
    }

    return Storage::download($files[0]);
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


  public function planos()
  {
    $operadoras = Operadora::where('empresa_id', Auth::user()->empresa_id)->get();
    return view("content.pages.backoffice.planos", [
      'operadoras' => $operadoras
    ]);
  }

  public function operadoras()
  {
    $operadoras = Operadora::where('empresa_id', Auth::user()->empresa_id)->get();
    return view("content.pages.backoffice.operadora");
  }

  public function createOperation(Request $request)
  {
    try {
      Operadora::insert([
        'empresa_id' => Auth::user()->empresa_id,
        'nome' => mb_strtoupper($request->nome, 'UTF-8'),
        'status' => $request->status,
        'created_at' => now(),
        'updated_at' => now()
      ]);
      return response()->json(['success' => true, 'message' => 'Operadora cadastrada com sucesso!'], 201);

    } catch (\Exception $e) {
      return response()->json(['success' => false, 'message' => 'Erro ao cadastrar operadora.'], 500);
    }
  }


  public function createPlan(Request $request)
  {
    try {
      Plano::insert([
        'empresa_id' => Auth::user()->empresa_id,
        'operadora_id' => $request->operadora_id,
        'nome' => mb_strtoupper($request->nome, 'UTF-8'),
        'status' => $request->status,
        'acomodacao' => $request->acomodacao,
        'created_at' => now(),
        'updated_at' => now()
      ]);
      return response()->json(['success' => true, 'message' => 'Plano cadastrado com sucesso!'], 201);
    } catch (\Exception $e) {
      return response()->json(['success' => false, 'message' => 'Erro ao cadastrar plano.'], 500);
    }
  }

  public function getOperators()
  {
    $operators = Operadora::where('empresa_id', Auth::user()->empresa_id)->get();
    return response()->json(
      $operators
    );
  }

  public function getPlans()
  {
    $plans = Plano::select(
      'planos.id',
      'operadoras.nome as operadora',
      'planos.status',
      'planos.acomodacao',
      'planos.created_at',
      'planos.nome'
    )
      ->leftJoin('operadoras', 'operadoras.id', '=', 'planos.operadora_id')
      ->where('planos.empresa_id', Auth::user()->empresa_id)
      ->orderBy('planos.created_at', 'desc')
      ->get();
    return response()->json($plans);
  }

}
