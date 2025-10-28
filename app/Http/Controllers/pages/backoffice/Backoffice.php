<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\Tabulations;
use App\Events\ContratoImplantado;
use App\Helpers\Helpers;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\Tabulacoes;
use App\Models\User;
use App\Models\Vendas;
use App\Models\VendaTitular;
use App\Notifications\StatusPropostaAlterada;
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
use App\Jobs\GerarRecebiveisJob;
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
    try {
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


        $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, $request->data_implantacao, $request->motivo_pendencia ?? null, null, $request->numero_proposta);
        dispatch(new GerarRecebiveisJob($sale->id));

        // Dispara evento de broadcast para usuários administrativos
        event(new ContratoImplantado(
          contratoId: $sale->id,
          nomeContrato: $sale->nome_contrato,
          numeroProposta: $request->numero_proposta ?? $sale->numero_proposta ?? 'N/A',
          operadora: $sale->operadora ?? 'N/A',
          empresaId: $sale->empresa_id,
          alteradoPorNome: Auth::user()->name ?? 'Sistema'
        ));
      }

      if ($request->tabulacao_id == Tabulations::BOLETO_DISPONIVEL) {
        $request->validate([
          'boleto_disponivel' => 'required|file|mimes:jpeg,jpg,png,pdf',
        ]);

        $file = $request->file('boleto_disponivel');
        $directory = "boleto_disponiveis/" . $sale->empresa_id . '/' . $sale->id;
        $fileName = "/boleto." . $file->getClientOriginalExtension();
        Storage::putFileAs($directory, $file, $fileName);
        $updateContract = $this->vendasRepository->saveTicket($sale->id, $directory . $fileName);
      }

      if ($request->tabulacao_id != Tabulations::IMPLANTADO && $request->tabulacao_id != Tabulations::BOLETO_DISPONIVEL) {
        $updateContract = $this->vendasRepository->updateDataImplantacao($sale->id, NULL, $request->motivo_pendencia ?? null, null, $request->numero_proposta);
      }

      if ($updateContract) {
        $tabulation = Tabulacoes::find($request->tabulacao_id);
        $vendedor = User::findOrFail($sale->user_id);
        $vendedor->notify(new StatusPropostaAlterada(
          vendaId: $sale->id,
          novoStatus: $tabulation->descricao,
          alteradoPorId: Auth::id(),
          alteradoPorNome: Auth::user()->name ?? null
        ));
        return redirect()->route(route: 'backoffice.index')->with('status', 'success')->with('message', "Contrato Atualizado");
      } else {
        return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', "Erro ao atualizar contrato ,contate nosso suporte");
      }
    } catch (\Throwable $th) {
      dd($th);
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

  public function storeTitular(Request $request)
  {
    try {
      // 1) valida apenas venda_id para poder decidir regra dinâmica
      $request->validate([
        'venda_id' => ['required', 'integer', 'exists:vendas,id'],
      ]);

      $venda = Vendas::findOrFail((int) $request->input('venda_id'));

      // segura: venda precisa pertencer à mesma empresa do usuário
      if ((int) $venda->empresa_id !== (int) Auth::user()->empresa_id) {
        return back()
          ->withInput()
          ->with('status', 'error')
          ->with('message', 'Acesso negado para esta venda.');
      }

      // Regra AMIL (qualquer variação contendo "AMIL")
      $isAmil = stripos((string) $venda->operadora, 'AMIL') !== false;

      // 2) validação completa agora que sabemos a regra de coparticipação
      $validated = $request->validate([
        'nome' => ['required', 'string', 'max:90'],
        'email' => ['nullable', 'email', 'max:90'],
        'telefone' => ['nullable', 'string', 'max:50'],
        'plano_id' => ['required', 'integer', 'exists:planos,id'],
        'coparticipacao' => ['required', Rule::in($isAmil ? ['PARCIAL', 'COMPLETA'] : ['Y', 'N'])],
      ]);

      // (Opcional forte) garantir que o PLANO pertence à operadora base do contrato
      $operadora = Operadora::where('empresa_id', Auth::user()->empresa_id)
        ->whereRaw('UPPER(nome) = ?', [mb_strtoupper((string) $venda->operadora, 'UTF-8')])
        ->first();

      if ($operadora) {
        $plano = Plano::findOrFail((int) $validated['plano_id']);
        if ((int) $plano->operadora_id !== (int) $operadora->id) {
          return back()
            ->withInput()
            ->with('status', 'error')
            ->with('message', 'Plano selecionado não pertence à operadora do contrato.');
        }
      }

      DB::transaction(function () use ($venda, $validated) {
        VendaTitular::create([
          'venda_id' => $venda->id,
          'nome' => mb_strtoupper($validated['nome'], 'UTF-8'),
          'email' => $validated['email'] ?? null,
          'telefone' => Helpers::cleanSpecialCharacters($validated['telefone'] ?? ''),
          'plano_id' => (int) $validated['plano_id'],
          'coparticipacao' => strtoupper($validated['coparticipacao']),
        ]);
      });

      return back()
        ->with('status', 'success')
        ->with('message', 'Titular cadastrado com sucesso.');
    } catch (\Illuminate\Validation\ValidationException $e) {
      return back()
        ->withErrors($e->errors())
        ->withInput()
        ->with('status', 'error')
        ->with('message', 'Erro de validação.');
    } catch (\Throwable $e) {
      // \Log::error('Erro ao criar titular', ['e' => $e]);
      return back()
        ->withInput()
        ->with('status', 'error')
        ->with('message', 'Falha ao cadastrar titular.');
    }
  }



}
