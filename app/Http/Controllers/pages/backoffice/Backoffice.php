<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\Tabulations;
use App\Helpers\Helpers;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\Vendas;
use App\Models\VendaTitular;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
    if (!$sale)
      abort(404, 'Contrato não encontrado.');

    $operadoras = Operadora::where('empresa_id', Auth::user()->empresa_id)->get();

    $selectedOperadora = $operadoras->first(function ($op) use ($sale) {
      return mb_strtoupper($op->nome, 'UTF-8') === mb_strtoupper($sale->operadora ?? '', 'UTF-8');
    });
    $selectedOperadoraId = optional($selectedOperadora)->id;

    $planosDaOperadora = $selectedOperadoraId
      ? Plano::where('operadora_id', $selectedOperadoraId)->get()
      : collect();

    $plano = $sale->plano_id ? Plano::find($sale->plano_id) : null;

    $titulares = VendaTitular::with('plano')
      ->where('venda_id', $sale->id)
      ->get();

    return view('content.pages.backoffice.openContract', [
      'contract' => $sale,
      'operadoras' => $operadoras,
      'selectedOperadoraId' => $selectedOperadoraId,
      'planosDaOperadora' => $planosDaOperadora,
      'plano' => $plano,
      'titulares' => $titulares,
    ]);
  }



  public function updateSale(Request $request)
  {
    if ($this->vendasRepository->updateContract($request->all())) {
      return redirect()->back()->with('status', 'success')->with('message', "Contrato Atualizado");
    } else {
      return redirect()->back()->with('status', 'error')->with('message', "Erro ao atualizar contrato ,contate nosso suporte");
    }
  }

  public function alterStatusContract(Request $request)
  {
    $sale = $this->vendasRepository->find($request->idSale);
    $updateContract = $this->contatosCorretoresRepository->alterStatusContract($sale->contato_id, $request->tabulacao_id);

    if ($request->tabulacao_id == Tabulations::IMPLANTADO) {
      $request->validate([
        'comprovante' => 'required|file|mimes:jpeg,jpg,png,pdf',
        'data_implantacao' => 'required|date',
      ]);

      $file = $request->file('comprovante');
      $directory = "comprovantes/" . $sale->empresa_id . '/' . $sale->id;
      $fileName = 'comprovante_pagamento.' . $file->getClientOriginalExtension();
      Storage::putFileAs($directory, $file, $fileName);

      $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, $request->data_implantacao, $request->motivo_pendencia ?? null);
    }

    if ($request->tabulacao_id == Tabulations::PENDENCIA) {
      $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, NULL, $request->motivo_pendencia ?? null);
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


  public function updateTitular(Request $request, int $id)
  {
    try {
      $titular = VendaTitular::findOrFail($id);

      $vendaId = (int) $request->input('venda_id');
      $venda = Vendas::findOrFail($vendaId);

      if ($titular->venda_id !== $venda->id) {
        return back()
          ->withInput()
          ->with('status', 'error')
          ->with('message', 'Titular não pertence a esta venda.');
      }

      if ((int) $venda->empresa_id !== (int) Auth::user()->empresa_id) {
        return back()
          ->with('status', 'error')
          ->with('message', 'Acesso negado para esta venda.');
      }


      $isAmil = stripos((string) $venda->operadora, 'AMIL - PME') !== false;

      // Validação
      $validated = $request->validate([
        'venda_id' => ['required', 'integer', 'exists:vendas,id'],
        'nome' => ['required', 'string', 'max:90'],
        'email' => ['nullable', 'email', 'max:90'],
        'telefone' => ['nullable', 'string', 'max:50'],
        'plano_id' => ['required', 'integer', 'exists:planos,id'],
        'coparticipacao' => ['required', Rule::in($isAmil ? ['PARCIAL', 'COMPLETA'] : ['Y', 'N'])],
      ]);

      DB::transaction(function () use ($titular, $validated) {
        $titular->update([
          'nome' => $validated['nome'], // se tiver mutator, fará uppercase
          'email' => $validated['email'] ?? null,
          'telefone' => Helpers::cleanSpecialCharacters($validated['telefone'] ?? ''),
          'plano_id' => (int) $validated['plano_id'],
          'coparticipacao' => strtoupper($validated['coparticipacao']),
        ]);
      });

      return back()
        ->with('status', 'success')
        ->with('message', 'Titular atualizado com sucesso.');
    } catch (\Illuminate\Validation\ValidationException $e) {
      return back()
        ->withErrors($e->errors())
        ->withInput()
        ->with('status', 'error')
        ->with('message', 'Erro de validação.');
    } catch (\Throwable $e) {
      // \Log::error('Erro ao atualizar titular', ['e' => $e]);
      return back()
        ->withInput()
        ->with('status', 'error')
        ->with('message', 'Falha ao atualizar titular.');
    }
  }


}
