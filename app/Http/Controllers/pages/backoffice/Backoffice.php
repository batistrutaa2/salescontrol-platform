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
use App\Models\VendaHistorico;
use App\Notifications\StatusPropostaAlterada;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Models\AcessoEmpresa;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Jobs\GerarRecebiveisJob;
use App\Models\Recebivel;
use App\Models\RegrasComissionamento;
use App\Models\PosVendaAnotacao;
use Carbon\Carbon;

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

        // Salvar acesso da empresa (se preenchido - campos opcionais)
        if ($request->filled('acesso_email') && $request->filled('acesso_senha')) {
          AcessoEmpresa::create([
            'venda_id' => $sale->id,
            'email' => $request->acesso_email,
            'senha' => $request->acesso_senha,
            'cpf' => $request->acesso_cpf,
          ]);
        }

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
      return redirect()->route(route: 'backoffice.index')->with('status', 'error')->with('message', "Erro ao atualizar contrato ,contate nosso suporte");
    }
  }

  /**
   * Mudança rápida de status via Kanban (sem modal)
   * Apenas para status que não requerem dados adicionais
   */
  public function quickStatusChange(Request $request)
  {
    try {
      $request->validate([
        'venda_id' => 'required|integer',
        'tabulacao_id' => 'required|integer',
      ]);

      $vendaId = $request->venda_id;
      $tabulacaoId = $request->tabulacao_id;

      // Status que requerem modal (não permitidos aqui)
      $statusComModal = [
        Tabulations::IMPLANTADO,
        Tabulations::PENDENCIA,
      ];

      if (in_array($tabulacaoId, $statusComModal)) {
        return response()->json([
          'success' => false,
          'message' => 'Este status requer informações adicionais. Use o modal.',
          'requires_modal' => true,
        ], 400);
      }

      $sale = $this->vendasRepository->find($vendaId);
      if (!$sale || $sale->empresa_id != Auth::user()->empresa_id) {
        return response()->json([
          'success' => false,
          'message' => 'Venda não encontrada.',
        ], 404);
      }

      // Atualizar status via contatos_corretores
      $updateContract = $this->contatosCorretoresRepository->alterStatusContract($sale->contato_id, $tabulacaoId);

      if ($updateContract) {
        // Notificar vendedor
        $tabulation = Tabulacoes::find($tabulacaoId);
        $vendedor = User::findOrFail($sale->user_id);
        $vendedor->notify(new StatusPropostaAlterada(
          vendaId: $sale->id,
          novoStatus: $tabulation->descricao,
          alteradoPorId: Auth::id(),
          alteradoPorNome: Auth::user()->name ?? null
        ));

        return response()->json([
          'success' => true,
          'message' => 'Status atualizado com sucesso!',
          'novo_status' => $tabulation->descricao,
        ]);
      }

      return response()->json([
        'success' => false,
        'message' => 'Erro ao atualizar status.',
      ], 500);

    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao atualizar status: ' . $th->getMessage(),
      ], 500);
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

  /**
   * Gera ou atualiza recebíveis para um contrato específico
   */
  public function gerarRecebivelContrato(int $vendaId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      // Buscar a venda e validar
      $venda = Vendas::select('vendas.*')
        ->join('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
        ->where('vendas.id', $vendaId)
        ->where('vendas.empresa_id', $empresaId)
        ->where('contatos_corretores.tabulacao_id', Tabulations::IMPLANTADO)
        ->first();

      if (!$venda) {
        return response()->json([
          'success' => false,
          'message' => 'Contrato não encontrado ou não está implantado.'
        ], 404);
      }

      if (!$venda->data_implantacao) {
        return response()->json([
          'success' => false,
          'message' => 'Contrato não possui data de implantação definida.'
        ], 400);
      }

      // Buscar operadora
      $operadora = Operadora::where('nome', $venda->operadora)->first();




      if (!$operadora) {
        return response()->json([
          'success' => false,
          'message' => "Operadora não encontrada: {$venda->operadora}"
        ], 400);
      }

      // Buscar regra de comissionamento
      $regra = RegrasComissionamento::with('parcelas')
        ->where('empresa_id', $venda->empresa_id)
        ->where('operadora_id', $operadora->id)
        ->first();

       

      if (!$regra) {
        return response()->json([
          'success' => false,
          'message' => "Nenhuma regra de comissionamento encontrada para a operadora {$operadora->nome}"
        ], 400);
      }

      // Resolver nome do plano
      $planoNome = 'N/A';
      if (!empty($venda->plano_id)) {
        $plano = Plano::find($venda->plano_id);
        $planoNome = $plano?->nome ?? $venda->nome_plano ?? 'N/A';
      } elseif (!empty($venda->nome_plano)) {
        $planoNome = $venda->nome_plano;
      }

      // Verificar se já existem recebíveis
      $recebiveisExistentes = Recebivel::where('venda_id', $vendaId)->count();

      // Gerar ou atualizar recebíveis conforme parcelas da regra
      $criados = 0;
      $atualizados = 0;

      foreach ($regra->parcelas()->orderBy('parcela')->get() as $parcela) {
        $valorParcela = ($parcela->percentual / 100) * $venda->valor_contrato;

        $recebivel = Recebivel::updateOrCreate(
          [
            'venda_id' => $venda->id,
            'parcela'  => $parcela->parcela,
          ],
          [
            'empresa_id'    => $venda->empresa_id,
            'vendedor_id'   => $venda->user_id,
            'operadora'     => $operadora->nome,
            'plano'         => $planoNome,
            'valor'         => $valorParcela,
            'data_prevista' => Carbon::parse($venda->data_implantacao)->addMonths($parcela->parcela - 1),
            'status'        => 'PENDENTE',
          ]
        );

        if ($recebivel->wasRecentlyCreated) {
          $criados++;
        } else {
          $atualizados++;
        }
      }

      // Montar mensagem de retorno
      $mensagem = '';
      if ($criados > 0 && $atualizados > 0) {
        $mensagem = "{$criados} recebível(is) criado(s) e {$atualizados} atualizado(s) com sucesso.";
      } elseif ($criados > 0) {
        $mensagem = "{$criados} recebível(is) gerado(s) com sucesso.";
      } else {
        $mensagem = "{$atualizados} recebível(is) atualizado(s) com sucesso.";
      }

      return response()->json([
        'success' => true,
        'message' => $mensagem,
        'criados' => $criados,
        'atualizados' => $atualizados,
        'ja_existia' => $recebiveisExistentes > 0
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao gerar recebíveis: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Verifica se um contrato possui recebíveis
   */
  public function verificarRecebiveis(int $vendaId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $venda = Vendas::where('id', $vendaId)
        ->where('empresa_id', $empresaId)
        ->first();

      if (!$venda) {
        return response()->json([
          'success' => false,
          'message' => 'Contrato não encontrado.'
        ], 404);
      }

      $recebiveis = Recebivel::where('venda_id', $vendaId)->get();
      $totalRecebiveis = $recebiveis->count();
      $valorTotal = $recebiveis->sum('valor');

      return response()->json([
        'success' => true,
        'possui_recebiveis' => $totalRecebiveis > 0,
        'quantidade' => $totalRecebiveis,
        'valor_total' => $valorTotal,
        'nome_contrato' => $venda->nome_contrato
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao verificar recebíveis: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Relatório de Performance do Backoffice
   * Analisa tempo médio entre status e gargalos no pipeline
   */
  public function relatorioPerformance()
  {
    return view('content.pages.backoffice.relatorio-performance');
  }

  /**
   * Busca dados do relatório de performance do backoffice
   */
  public function getPerformanceData(Request $request)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      // Suporta tanto start_date/end_date quanto mes/ano
      if ($request->has('mes') && $request->has('ano')) {
        $mes = $request->input('mes');
        $ano = $request->input('ano');
        $startDate = Carbon::create($ano, $mes, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::create($ano, $mes, 1)->endOfMonth()->format('Y-m-d');
      } else {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
      }

      // Status do backoffice em ordem de pipeline
      $statusPipeline = [
        'VENDA' => ['ordem' => 1, 'tipo' => 'entrada'],
        'ANALISE DE DOCUMENTOS' => ['ordem' => 2, 'tipo' => 'processo'],
        'PENDENCIA' => ['ordem' => 3, 'tipo' => 'bloqueio'],
        'REGULARIZADO' => ['ordem' => 4, 'tipo' => 'processo'],
        'ANALISE OPERADORA' => ['ordem' => 5, 'tipo' => 'processo'],
        'CONTR. GERADO - AGUARDANDO ASSINATURA' => ['ordem' => 6, 'tipo' => 'processo'],
        'AGUARD. ASSINATURA DA DS' => ['ordem' => 7, 'tipo' => 'processo'],
        'BOLETO DISPONIVEL' => ['ordem' => 8, 'tipo' => 'processo'],
        'IMPLANTADO' => ['ordem' => 9, 'tipo' => 'sucesso'],
        'ESTORNO' => ['ordem' => 10, 'tipo' => 'falha'],
        'DECLINADO' => ['ordem' => 11, 'tipo' => 'falha'],
      ];

      // Buscar vendas com seus status e datas
      $vendas = DB::table('vendas')
        ->select([
          'vendas.id',
          'vendas.numero_proposta',
          'vendas.nome_contrato',
          'vendas.operadora',
          'vendas.valor_contrato',
          'vendas.created_at as data_venda',
          'vendas.data_implantacao',
          'vendas.updated_at',
          'users.name as vendedor',
          'tabulacoes.descricao as status_atual',
          'contatos_corretores.updated_at as status_updated_at',
          'vendas.motivo_pendencia'
        ])
        ->leftJoin('users', 'users.id', '=', 'vendas.user_id')
        ->leftJoin('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->where('vendas.empresa_id', $empresaId)
        ->whereBetween('vendas.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ->orderBy('vendas.created_at', 'desc')
        ->get();

      // Métricas gerais
      $totalVendas = $vendas->count();
      $vendasImplantadas = $vendas->where('status_atual', 'IMPLANTADO')->count();
      $vendasEmAndamento = $vendas->whereNotIn('status_atual', ['IMPLANTADO', 'ESTORNO', 'DECLINADO'])->count();
      $vendasPerdidas = $vendas->whereIn('status_atual', ['ESTORNO', 'DECLINADO'])->count();

      // Calcular tempo médio até implantação (apenas para implantados)
      $vendasImplantadasComData = $vendas->filter(function ($v) {
        return $v->status_atual === 'IMPLANTADO' && $v->data_implantacao && $v->data_venda;
      });

      $tempoMedioImplantacao = 0;
      $tempoMinImplantacao = 0;
      $tempoMaxImplantacao = 0;
      $temposImplantacao = [];

      foreach ($vendasImplantadasComData as $v) {
        $dataVenda = Carbon::parse($v->data_venda);
        $dataImpl = Carbon::parse($v->data_implantacao);
        $dias = $dataVenda->diffInDays($dataImpl);
        $temposImplantacao[] = $dias;
      }

      if (count($temposImplantacao) > 0) {
        $tempoMedioImplantacao = round(array_sum($temposImplantacao) / count($temposImplantacao), 1);
        $tempoMinImplantacao = min($temposImplantacao);
        $tempoMaxImplantacao = max($temposImplantacao);
      }

      // Calcular tempo médio em cada status (baseado em updated_at)
      $tempoMedioPorStatus = [];
      foreach ($vendas->whereNotNull('status_atual') as $v) {
        $status = $v->status_atual;
        if (!isset($tempoMedioPorStatus[$status])) {
          $tempoMedioPorStatus[$status] = ['total_dias' => 0, 'count' => 0, 'em_aberto' => 0];
        }

        if (!in_array($status, ['IMPLANTADO', 'ESTORNO', 'DECLINADO'])) {
          // Status em aberto - calcular dias desde o update
          $diasEmStatus = Carbon::parse($v->status_updated_at)->diffInDays(now());
          $tempoMedioPorStatus[$status]['total_dias'] += $diasEmStatus;
          $tempoMedioPorStatus[$status]['em_aberto']++;
        }
        $tempoMedioPorStatus[$status]['count']++;
      }

      // Distribuição por status
      $distribuicaoStatus = $vendas->groupBy('status_atual')->map(function ($items, $status) use ($totalVendas, $statusPipeline) {
        $count = $items->count();
        $valorTotal = $items->sum('valor_contrato');
        $info = $statusPipeline[$status] ?? ['ordem' => 99, 'tipo' => 'desconhecido'];

        return [
          'status' => $status,
          'quantidade' => $count,
          'percentual' => $totalVendas > 0 ? round(($count / $totalVendas) * 100, 1) : 0,
          'valor_total' => $valorTotal,
          'ordem' => $info['ordem'],
          'tipo' => $info['tipo'],
        ];
      })->sortBy('ordem')->values();

      // Gargalos - Status com mais tempo de espera
      $gargalos = [];
      foreach ($vendas->whereNotIn('status_atual', ['IMPLANTADO', 'ESTORNO', 'DECLINADO', null]) as $v) {
        $diasEmStatus = Carbon::parse($v->status_updated_at)->diffInDays(now());

        if ($diasEmStatus >= 3) { // Considerar gargalo após 3 dias
          $gargalos[] = [
            'id' => $v->id,
            'numero_proposta' => $v->numero_proposta ?? '-',
            'nome_contrato' => $v->nome_contrato,
            'status_atual' => $v->status_atual,
            'dias_parado' => $diasEmStatus,
            'vendedor' => $v->vendedor ?? 'N/A',
            'operadora' => $v->operadora ?? 'N/A',
            'valor_contrato' => $v->valor_contrato,
            'motivo_pendencia' => $v->motivo_pendencia,
          ];
        }
      }

      // Ordenar gargalos por dias parado (desc)
      usort($gargalos, fn($a, $b) => $b['dias_parado'] <=> $a['dias_parado']);

      // Análise por operadora
      $porOperadora = $vendas->groupBy('operadora')->map(function ($items, $operadora) {
        $total = $items->count();
        $implantados = $items->where('status_atual', 'IMPLANTADO')->count();
        $perdidos = $items->whereIn('status_atual', ['ESTORNO', 'DECLINADO'])->count();
        $emAndamento = $total - $implantados - $perdidos;

        // Tempo médio de implantação por operadora
        $tempos = [];
        foreach ($items->where('status_atual', 'IMPLANTADO') as $v) {
          if ($v->data_implantacao && $v->data_venda) {
            $tempos[] = Carbon::parse($v->data_venda)->diffInDays(Carbon::parse($v->data_implantacao));
          }
        }

        return [
          'operadora' => $operadora ?: 'N/A',
          'total' => $total,
          'implantados' => $implantados,
          'em_andamento' => $emAndamento,
          'perdidos' => $perdidos,
          'taxa_conversao' => $total > 0 ? round(($implantados / $total) * 100, 1) : 0,
          'tempo_medio' => count($tempos) > 0 ? round(array_sum($tempos) / count($tempos), 1) : null,
          'valor_total' => $items->sum('valor_contrato'),
        ];
      })->sortByDesc('total')->values();

      // Análise por vendedor
      $porVendedor = $vendas->groupBy('vendedor')->map(function ($items, $vendedor) {
        $total = $items->count();
        $implantados = $items->where('status_atual', 'IMPLANTADO')->count();
        $perdidos = $items->whereIn('status_atual', ['ESTORNO', 'DECLINADO'])->count();

        $tempos = [];
        foreach ($items->where('status_atual', 'IMPLANTADO') as $v) {
          if ($v->data_implantacao && $v->data_venda) {
            $tempos[] = Carbon::parse($v->data_venda)->diffInDays(Carbon::parse($v->data_implantacao));
          }
        }

        return [
          'vendedor' => $vendedor ?: 'N/A',
          'total' => $total,
          'implantados' => $implantados,
          'perdidos' => $perdidos,
          'taxa_conversao' => $total > 0 ? round(($implantados / $total) * 100, 1) : 0,
          'tempo_medio' => count($tempos) > 0 ? round(array_sum($tempos) / count($tempos), 1) : null,
          'valor_implantado' => $items->where('status_atual', 'IMPLANTADO')->sum('valor_contrato'),
        ];
      })->sortByDesc('implantados')->values();

      // Evolução mensal
      $evolucaoMensal = $vendas->groupBy(function ($item) {
        return Carbon::parse($item->data_venda)->format('Y-m');
      })->map(function ($items, $mes) {
        $total = $items->count();
        $implantados = $items->where('status_atual', 'IMPLANTADO')->count();

        return [
          'mes' => Carbon::parse($mes . '-01')->translatedFormat('M/Y'),
          'mes_raw' => $mes,
          'vendas' => $total,
          'implantados' => $implantados,
          'taxa_conversao' => $total > 0 ? round(($implantados / $total) * 100, 1) : 0,
        ];
      })->sortKeys()->values();

      // Pipeline funil - quantidade em cada etapa
      $pipelineFunil = [];
      foreach ($statusPipeline as $status => $info) {
        $quantidade = $vendas->where('status_atual', $status)->count();
        if ($quantidade > 0 || $info['tipo'] !== 'falha') {
          $pipelineFunil[] = [
            'status' => $status,
            'etapa' => $status,
            'quantidade' => $quantidade,
            'ordem' => $info['ordem'],
            'tipo' => $info['tipo'],
          ];
        }
      }
      usort($pipelineFunil, fn($a, $b) => $a['ordem'] <=> $b['ordem']);

      // Tempo médio por etapa (baseado em dias no status)
      $tempoPorEtapa = [];
      foreach ($tempoMedioPorStatus as $status => $dados) {
        if ($dados['em_aberto'] > 0) {
          $tempoPorEtapa[] = [
            'etapa' => $status,
            'media_dias' => round($dados['total_dias'] / $dados['em_aberto'], 1),
            'contratos' => $dados['em_aberto'],
          ];
        }
      }
      usort($tempoPorEtapa, fn($a, $b) => $b['media_dias'] <=> $a['media_dias']);

      // Análise de pendências (motivos mais comuns)
      $pendencias = $vendas->whereNotNull('motivo_pendencia')
        ->where('motivo_pendencia', '!=', '')
        ->groupBy('motivo_pendencia')
        ->map(function ($items, $motivo) {
          return [
            'motivo' => $motivo,
            'quantidade' => $items->count(),
          ];
        })
        ->sortByDesc('quantidade')
        ->take(10)
        ->values();

      return response()->json([
        'success' => true,
        'periodo' => [
          'inicio' => Carbon::parse($startDate)->format('d/m/Y'),
          'fim' => Carbon::parse($endDate)->format('d/m/Y'),
        ],
        'metricas' => [
          'total_vendas' => $totalVendas,
          'implantadas' => $vendasImplantadas,
          'em_andamento' => $vendasEmAndamento,
          'perdidas' => $vendasPerdidas,
          'taxa_conversao' => $totalVendas > 0 ? round(($vendasImplantadas / $totalVendas) * 100, 1) : 0,
          'taxa_perda' => $totalVendas > 0 ? round(($vendasPerdidas / $totalVendas) * 100, 1) : 0,
          'tempo_medio_implantacao' => $tempoMedioImplantacao,
          'tempo_min_implantacao' => $tempoMinImplantacao,
          'tempo_max_implantacao' => $tempoMaxImplantacao,
          'valor_total' => $vendas->sum('valor_contrato'),
          'valor_implantado' => $vendas->where('status_atual', 'IMPLANTADO')->sum('valor_contrato'),
          'valor_perdido' => $vendas->whereIn('status_atual', ['ESTORNO', 'DECLINADO'])->sum('valor_contrato'),
        ],
        'distribuicao_status' => $distribuicaoStatus,
        'pipeline_funil' => $pipelineFunil,
        'tempo_por_etapa' => $tempoPorEtapa,
        'gargalos' => array_slice($gargalos, 0, 20),
        'total_gargalos' => count($gargalos),
        'por_operadora' => $porOperadora,
        'por_vendedor' => $porVendedor,
        'evolucao_mensal' => $evolucaoMensal,
        'pendencias_frequentes' => $pendencias,
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar dados: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Retorna dados para o pipeline visual
   */
  public function getPipelineData(Request $request)
  {
    try {
      $empresaId = Auth::user()->empresa_id;
      $dataInicio = $request->input('data_inicio');
      $dataFim = $request->input('data_fim');
      $vendedorId = $request->input('vendedor_id');
      $busca = $request->input('busca');

      // Buscar vendas com status de backoffice usando contatos_corretores
      // O status real da venda vem do contato_id vinculado em contatos_corretores
      $query = DB::table('vendas')
        ->select([
          'vendas.id',
          'vendas.numero_proposta',
          'vendas.nome_contrato',
          'vendas.operadora',
          'vendas.nome_plano',
          'vendas.valor_contrato',
          'vendas.created_at as data_venda',
          'vendas.data_implantacao',
          'vendas.contato_id',
          'vendas.motivo_pendencia',
          'users.name as vendedor',
          'users.id as vendedor_id',
          'tabulacoes.id as tabulacao_id',
          'tabulacoes.descricao as status_atual',
          'tabulacoes.ordem_kanban',
          'contatos_corretores.updated_at as status_updated_at',
        ])
        ->leftJoin('users', 'users.id', '=', 'vendas.user_id')
        ->leftJoin('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->where('vendas.empresa_id', $empresaId)
        ->where('tabulacoes.tipo_tabulacao', 'A'); // Apenas tabulações de backoffice

      if ($dataInicio) {
        $query->whereDate('vendas.created_at', '>=', $dataInicio);
      }
      if ($dataFim) {
        $query->whereDate('vendas.created_at', '<=', $dataFim);
      }
      if ($vendedorId) {
        $query->where('vendas.user_id', $vendedorId);
      }
      if ($busca) {
        $query->where(function ($q) use ($busca) {
          $q->where('vendas.nome_contrato', 'like', "%{$busca}%")
            ->orWhere('vendas.numero_proposta', 'like', "%{$busca}%")
            ->orWhere('vendas.operadora', 'like', "%{$busca}%");
        });
      }

      $vendas = $query->orderBy('vendas.created_at', 'desc')->get();

      // Status permitidos no Kanban (ordem definida pelo usuário)
      $statusPermitidos = [
        'VENDA',
        'ANALISE DE DOCUMENTOS',
        'ANALISE OPERADORA',
        'PENDENCIA',
        'REGULARIZADO',
        'AGUARD. ASSINATURA DA DS',
        'BOLETO DISPONIVEL',
      ];

      // Buscar tabulações do backoffice APENAS dos status permitidos
      $tabulacoes = Tabulacoes::where('empresa_id', $empresaId)
        ->where('tipo_tabulacao', 'A')
        ->where('status', 'Y')
        ->whereIn('descricao', $statusPermitidos)
        ->get()
        ->sortBy(function ($tab) use ($statusPermitidos) {
          // Ordenar pela posição no array de status permitidos
          return array_search($tab->descricao, $statusPermitidos);
        })
        ->values();

      // Agrupar vendas por status
      $pipeline = [];
      foreach ($tabulacoes as $tab) {
        $vendasNoStatus = $vendas->where('tabulacao_id', $tab->id);

        // Calcular contratos atrasados neste status
        $atrasados = 0;
        $prazoMaximo = $this->getPrazoMaximo($tab->descricao);

        foreach ($vendasNoStatus as $v) {
          $dataReferencia = $v->status_updated_at ?? $v->data_venda;
          $diasNoStatus = $dataReferencia ? (int) Carbon::parse($dataReferencia)->diffInDays(now()) : 0;
          if ($prazoMaximo && $diasNoStatus > $prazoMaximo) {
            $atrasados++;
          }
        }

        $pipeline[] = [
          'id' => $tab->id,
          'nome' => $tab->descricao,
          'ordem' => $tab->ordem_kanban,
          'cor' => $this->getCorStatus($tab->descricao),
          'icone' => $this->getIconeStatus($tab->descricao),
          'quantidade' => $vendasNoStatus->count(),
          'valor_total' => $vendasNoStatus->sum('valor_contrato'),
          'atrasados' => $atrasados,
          'contratos' => $vendasNoStatus->map(function ($v) {
            // Se status_updated_at for nulo, usar data_venda como fallback
            $dataReferencia = $v->status_updated_at ?? $v->data_venda;
            $diasNoStatus = $dataReferencia ? (int) Carbon::parse($dataReferencia)->diffInDays(now()) : 0;
            $prazoMaximo = $this->getPrazoMaximo($v->status_atual);

            return [
              'id' => $v->id,
              'venda_id' => $v->id, // Alias para compatibilidade
              'numero_proposta' => $v->numero_proposta,
              'nome_contrato' => $v->nome_contrato,
              'operadora' => $v->operadora,
              'plano' => $v->nome_plano,
              'valor' => $v->valor_contrato,
              'vendedor' => $v->vendedor,
              'vendedor_id' => $v->vendedor_id,
              'data_venda' => Carbon::parse($v->data_venda)->format('d/m/Y'),
              'dias_no_status' => $diasNoStatus,
              'prazo_maximo' => $prazoMaximo, // Para o badge de atraso
              'atrasado' => $prazoMaximo && $diasNoStatus > $prazoMaximo,
              'motivo_pendencia' => $v->motivo_pendencia,
              'contato_id' => $v->contato_id,
            ];
          })->values(),
        ];
      }

      // KPIs
      $total = $vendas->count();
      $implantados = $vendas->where('status_atual', 'IMPLANTADO')->count();
      $emAndamento = $vendas->whereNotIn('status_atual', ['IMPLANTADO', 'ESTORNO', 'DECLINADO'])->count();
      $perdidos = $vendas->whereIn('status_atual', ['ESTORNO', 'DECLINADO'])->count();

      // Tempo médio de implantação
      $temposImplantacao = [];
      foreach ($vendas->where('status_atual', 'IMPLANTADO') as $v) {
        if ($v->data_implantacao && $v->data_venda) {
          $temposImplantacao[] = Carbon::parse($v->data_venda)->diffInDays(Carbon::parse($v->data_implantacao));
        }
      }
      $tempoMedio = count($temposImplantacao) > 0 ? round(array_sum($temposImplantacao) / count($temposImplantacao), 1) : 0;

      // Vendedores para filtro - busca vendedores que têm vendas no sistema
      $vendedores = User::select('users.id', 'users.name')
        ->join('vendas', 'vendas.user_id', '=', 'users.id')
        ->where('vendas.empresa_id', $empresaId)
        ->distinct()
        ->orderBy('users.name')
        ->get();

      return response()->json([
        'success' => true,
        'pipeline' => $pipeline,
        'kpis' => [
          'total' => $total,
          'implantados' => $implantados,
          'em_andamento' => $emAndamento,
          'perdidos' => $perdidos,
          'taxa_conversao' => $total > 0 ? round(($implantados / $total) * 100, 1) : 0,
          'tempo_medio' => $tempoMedio,
          'valor_total' => $vendas->sum('valor_contrato'),
          'valor_implantado' => $vendas->where('status_atual', 'IMPLANTADO')->sum('valor_contrato'),
        ],
        'vendedores' => $vendedores,
        'tabulacoes' => $tabulacoes,
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar dados: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Retorna contratos de um status específico
   */
  public function getContratosPorStatus(Request $request, int $tabulacaoId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $contratos = DB::table('vendas')
        ->select([
          'vendas.id',
          'vendas.numero_proposta',
          'vendas.nome_contrato',
          'vendas.operadora',
          'vendas.nome_plano',
          'vendas.valor_contrato',
          'vendas.created_at as data_venda',
          'vendas.contato_id',
          'vendas.motivo_pendencia',
          'users.name as vendedor',
          'tabulacoes.descricao as status_atual',
          'contatos_corretores.updated_at as status_updated_at',
        ])
        ->leftJoin('users', 'users.id', '=', 'vendas.user_id')
        ->leftJoin('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
        ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
        ->where('vendas.empresa_id', $empresaId)
        ->where('contatos_corretores.tabulacao_id', $tabulacaoId)
        ->orderBy('contatos_corretores.updated_at', 'asc')
        ->get();

      $contratosFormatados = $contratos->map(function ($c) {
        $diasNoStatus = Carbon::parse($c->status_updated_at)->diffInDays(now());
        $prazoMaximo = $this->getPrazoMaximo($c->status_atual);

        return [
          'id' => $c->id,
          'numero_proposta' => $c->numero_proposta,
          'nome_contrato' => $c->nome_contrato,
          'operadora' => $c->operadora,
          'plano' => $c->nome_plano,
          'valor' => $c->valor_contrato,
          'vendedor' => $c->vendedor,
          'data_venda' => Carbon::parse($c->data_venda)->format('d/m/Y'),
          'dias_no_status' => $diasNoStatus,
          'atrasado' => $prazoMaximo && $diasNoStatus > $prazoMaximo,
          'motivo_pendencia' => $c->motivo_pendencia,
          'contato_id' => $c->contato_id,
        ];
      });

      return response()->json([
        'success' => true,
        'contratos' => $contratosFormatados,
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar contratos: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Retorna histórico de uma venda
   */
  public function getHistorico(int $vendaId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      // Verificar se a venda pertence à empresa
      $venda = Vendas::where('id', $vendaId)
        ->where('empresa_id', $empresaId)
        ->first();

      if (!$venda) {
        return response()->json([
          'success' => false,
          'message' => 'Venda não encontrada.'
        ], 404);
      }

      // Buscar histórico
      $historico = VendaHistorico::with(['usuario', 'tabulacaoAnterior', 'tabulacaoNova'])
        ->where('venda_id', $vendaId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($h) {
          return [
            'id' => $h->id,
            'data' => $h->created_at->format('d/m/Y H:i'),
            'usuario' => $h->usuario->name ?? 'Sistema',
            'status_anterior' => $h->tabulacaoAnterior->descricao ?? 'N/A',
            'status_novo' => $h->tabulacaoNova->descricao ?? 'N/A',
            'observacao' => $h->observacao,
            'motivo_pendencia' => $h->motivo_pendencia,
            'tempo_formatado' => $h->tempo_formatado,
          ];
        });

      // Informações da venda
      $infoVenda = [
        'id' => $venda->id,
        'numero_proposta' => $venda->numero_proposta,
        'nome_contrato' => $venda->nome_contrato,
        'operadora' => $venda->operadora,
        'valor_contrato' => $venda->valor_contrato,
        'data_criacao' => Carbon::parse($venda->created_at)->format('d/m/Y H:i'),
        'data_implantacao' => $venda->data_implantacao ? Carbon::parse($venda->data_implantacao)->format('d/m/Y') : null,
      ];

      return response()->json([
        'success' => true,
        'venda' => $infoVenda,
        'historico' => $historico,
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar histórico: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Retorna prazo máximo para cada status (em dias)
   */
  private function getPrazoMaximo($status)
  {
    $prazos = [
      'ANALISE DE DOCUMENTOS' => 2,
      'PENDENCIA' => 2,
      'ANALISE OPERADORA' => 10,
      'CONTR. GERADO - AGUARDANDO ASSINATURA' => 5,
      'AGUARD. ASSINATURA DA DS' => 5,
      'BOLETO DISPONIVEL' => 3,
      'REGULARIZADO' => 2,
    ];

    return $prazos[$status] ?? null;
  }

  /**
   * Retorna cor para cada status
   */
  private function getCorStatus($status)
  {
    $cores = [
      'VENDA' => '#696cff',
      'ANALISE DE DOCUMENTOS' => '#03c3ec',
      'PENDENCIA' => '#ff3e1d',
      'REGULARIZADO' => '#71dd37',
      'ANALISE OPERADORA' => '#ffab00',
      'CONTR. GERADO - AGUARDANDO ASSINATURA' => '#8c57ff',
      'AGUARD. ASSINATURA DA DS' => '#ffc107',
      'BOLETO DISPONIVEL' => '#20c997',
      'IMPLANTADO' => '#198754',
      'ESTORNO' => '#dc3545',
      'DECLINADO' => '#6c757d',
    ];

    return $cores[$status] ?? '#8592a3';
  }

  /**
   * Retorna ícone para cada status
   */
  private function getIconeStatus($status)
  {
    $icones = [
      'VENDA' => 'ri-shopping-cart-line',
      'ANALISE DE DOCUMENTOS' => 'ri-file-search-line',
      'PENDENCIA' => 'ri-error-warning-line',
      'REGULARIZADO' => 'ri-checkbox-circle-line',
      'ANALISE OPERADORA' => 'ri-search-eye-line',
      'CONTR. GERADO - AGUARDANDO ASSINATURA' => 'ri-draft-line',
      'AGUARD. ASSINATURA DA DS' => 'ri-pen-nib-line',
      'BOLETO DISPONIVEL' => 'ri-bank-card-line',
      'IMPLANTADO' => 'ri-check-double-line',
      'ESTORNO' => 'ri-arrow-go-back-line',
      'DECLINADO' => 'ri-close-circle-line',
    ];

    return $icones[$status] ?? 'ri-question-line';
  }

  /**
   * Lista os acessos de uma venda
   */
  public function getAcessosEmpresa(int $vendaId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $venda = Vendas::where('id', $vendaId)
        ->where('empresa_id', $empresaId)
        ->first();

      if (!$venda) {
        return response()->json([
          'success' => false,
          'message' => 'Venda não encontrada.'
        ], 404);
      }

      $acessos = AcessoEmpresa::where('venda_id', $vendaId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($acesso) {
          return [
            'id' => $acesso->id,
            'email' => $acesso->email,
            'senha' => $acesso->senha,
            'cpf' => $acesso->cpf,
            'created_at' => $acesso->created_at ? Carbon::parse($acesso->created_at)->format('d/m/Y H:i') : null,
          ];
        });

      return response()->json([
        'success' => true,
        'acessos' => $acessos,
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar acessos: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Cadastra um novo acesso para a empresa/venda
   */
  public function storeAcessoEmpresa(Request $request)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $validated = $request->validate([
        'venda_id' => ['required', 'integer', 'exists:vendas,id'],
        'email' => ['required', 'email', 'max:150'],
        'senha' => ['required', 'string', 'max:255'],
        'cpf' => ['nullable', 'string', 'max:14'],
      ]);

      $venda = Vendas::where('id', $validated['venda_id'])
        ->where('empresa_id', $empresaId)
        ->first();

      if (!$venda) {
        return response()->json([
          'success' => false,
          'message' => 'Venda não encontrada ou acesso negado.'
        ], 404);
      }

      $acesso = AcessoEmpresa::create([
        'venda_id' => $venda->id,
        'email' => $validated['email'],
        'senha' => $validated['senha'],
        'cpf' => $validated['cpf'] ?? null,
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Acesso cadastrado com sucesso.',
        'acesso' => [
          'id' => $acesso->id,
          'email' => $acesso->email,
          'senha' => $acesso->senha,
          'cpf' => $acesso->cpf,
          'created_at' => $acesso->created_at ? Carbon::parse($acesso->created_at)->format('d/m/Y H:i') : null,
        ],
      ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro de validação.',
        'errors' => $e->errors(),
      ], 422);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao cadastrar acesso: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Atualiza um acesso existente
   */
  public function updateAcessoEmpresa(Request $request, int $id)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $validated = $request->validate([
        'email' => ['required', 'email', 'max:150'],
        'senha' => ['required', 'string', 'max:255'],
        'cpf' => ['nullable', 'string', 'max:14'],
      ]);

      $acesso = AcessoEmpresa::with('venda')->findOrFail($id);

      if ((int) $acesso->venda->empresa_id !== (int) $empresaId) {
        return response()->json([
          'success' => false,
          'message' => 'Acesso negado.'
        ], 403);
      }

      $acesso->update([
        'email' => $validated['email'],
        'senha' => $validated['senha'],
        'cpf' => $validated['cpf'] ?? null,
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Acesso atualizado com sucesso.',
        'acesso' => [
          'id' => $acesso->id,
          'email' => $acesso->email,
          'senha' => $acesso->senha,
          'cpf' => $acesso->cpf,
          'created_at' => $acesso->created_at ? Carbon::parse($acesso->created_at)->format('d/m/Y H:i') : null,
        ],
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro de validação.',
        'errors' => $e->errors(),
      ], 422);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao atualizar acesso: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Remove um acesso
   */
  public function deleteAcessoEmpresa(int $id)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $acesso = AcessoEmpresa::with('venda')->findOrFail($id);

      if ((int) $acesso->venda->empresa_id !== (int) $empresaId) {
        return response()->json([
          'success' => false,
          'message' => 'Acesso negado.'
        ], 403);
      }

      $acesso->delete();

      return response()->json([
        'success' => true,
        'message' => 'Acesso removido com sucesso.',
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao remover acesso: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Exibe a página de Pós-Venda (contratos implantados)
   */
  public function posVenda()
  {
    $empresaId = Auth::user()->empresa_id;

    // Buscar operadoras para filtro (apenas de contratos implantados)
    $operadoras = Vendas::select('operadora')
      ->where('empresa_id', $empresaId)
      ->whereNotNull('data_implantacao')
      ->whereNotNull('operadora')
      ->where('operadora', '!=', '')
      ->distinct()
      ->orderBy('operadora')
      ->pluck('operadora');

    // Buscar vendedores para filtro (apenas que têm contratos implantados)
    $vendedores = User::select('users.id', 'users.name')
      ->join('vendas', 'vendas.user_id', '=', 'users.id')
      ->join('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
      ->where('vendas.empresa_id', $empresaId)
      ->where('contatos_corretores.tabulacao_id', Tabulations::IMPLANTADO)
      ->whereNotNull('vendas.data_implantacao')
      ->distinct()
      ->orderBy('users.name')
      ->get();

    return view('content.pages.backoffice.pos-venda', compact('operadoras', 'vendedores'));
  }

  /**
   * API: Retorna dados dos contratos implantados para Pós-Venda
   */
  public function getPosVendaData(Request $request)
  {
    try {
      $empresaId = Auth::user()->empresa_id;
      $hoje = Carbon::now();

      $query = DB::table('vendas')
        ->select([
          'vendas.id',
          'vendas.numero_proposta',
          'vendas.nome_contrato',
          'vendas.cpf_cnpj',
          'vendas.operadora',
          'vendas.nome_plano',
          'vendas.valor_contrato',
          'vendas.vidas',
          'vendas.data_implantacao',
          'vendas.obs_contrato',
          'users.name as vendedor',
        ])
        ->leftJoin('users', 'users.id', '=', 'vendas.user_id')
        ->leftJoin('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
        ->where('vendas.empresa_id', $empresaId)
        ->where('contatos_corretores.tabulacao_id', Tabulations::IMPLANTADO)
        ->whereNotNull('vendas.data_implantacao');

      // Aplicar filtros
      if ($request->filled('operadora')) {
        $query->where('vendas.operadora', $request->operadora);
      }
      if ($request->filled('vendedor_id')) {
        $query->where('vendas.user_id', $request->vendedor_id);
      }
      if ($request->filled('mes_aniversario')) {
        $query->whereRaw('MONTH(vendas.data_implantacao) = ?', [$request->mes_aniversario]);
      }
      if ($request->filled('busca')) {
        $busca = $request->busca;
        $query->where(function ($q) use ($busca) {
          $q->where('vendas.nome_contrato', 'like', "%{$busca}%")
            ->orWhere('vendas.numero_proposta', 'like', "%{$busca}%")
            ->orWhere('vendas.cpf_cnpj', 'like', "%{$busca}%");
        });
      }

      $contratos = $query->orderBy('vendas.data_implantacao', 'desc')->get();

      // Processar dados
      $resultado = $contratos->map(function ($c) use ($hoje) {
        $dataImpl = Carbon::parse($c->data_implantacao);
        $mesesImplantado = (int) $dataImpl->diffInMonths($hoje);

        // Calcular próximo aniversário
        $proximoAniversario = $dataImpl->copy()->year($hoje->year);
        if ($proximoAniversario->isPast()) {
          $proximoAniversario->addYear();
        }
        $diasParaAniversario = (int) $hoje->diffInDays($proximoAniversario, false);

        return [
          'id' => $c->id,
          'numero_proposta' => $c->numero_proposta,
          'nome_contrato' => $c->nome_contrato,
          'cpf_cnpj' => $c->cpf_cnpj,
          'operadora' => $c->operadora,
          'nome_plano' => $c->nome_plano,
          'valor_contrato' => $c->valor_contrato,
          'vidas' => $c->vidas,
          'vendedor' => $c->vendedor,
          'data_implantacao' => $dataImpl->format('d/m/Y'),
          'meses_implantado' => $mesesImplantado,
          'proximo_aniversario' => $proximoAniversario->format('d/m'),
          'dias_para_aniversario' => $diasParaAniversario,
          'obs_contrato' => $c->obs_contrato,
        ];
      });

      // KPIs
      $totalImplantados = $resultado->count();

      // Aniversários no mês atual (contratos cuja data de implantação é no mesmo mês)
      $aniversariosNoMes = $resultado->filter(function ($c) use ($hoje) {
        $partes = explode('/', $c['proximo_aniversario']);
        if (count($partes) === 2) {
          return (int) $partes[1] === $hoje->month;
        }
        return false;
      })->count();

      // Próximos aniversários (em até 30 dias)
      $proximosAniversarios = $resultado->filter(function ($c) {
        return $c['dias_para_aniversario'] >= 0 && $c['dias_para_aniversario'] <= 30;
      })->count();

      $valorCarteira = $resultado->sum('valor_contrato');

      return response()->json([
        'success' => true,
        'contratos' => $resultado->values(),
        'kpis' => [
          'total_implantados' => $totalImplantados,
          'aniversarios_mes' => $aniversariosNoMes,
          'proximos_aniversarios' => $proximosAniversarios,
          'valor_carteira' => $valorCarteira,
        ],
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar dados: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Lista anotações pós-venda de um contrato
   */
  public function getAnotacoesPosVenda(int $vendaId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $venda = Vendas::where('id', $vendaId)
        ->where('empresa_id', $empresaId)
        ->first();

      if (!$venda) {
        return response()->json([
          'success' => false,
          'message' => 'Venda não encontrada.'
        ], 404);
      }

      $anotacoes = PosVendaAnotacao::with('usuario:id,name')
        ->where('venda_id', $vendaId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(fn($a) => [
          'id' => $a->id,
          'descricao' => $a->descricao,
          'usuario' => $a->usuario->name ?? 'N/A',
          'data' => $a->created_at->format('d/m/Y H:i'),
          'data_relativa' => $a->created_at->diffForHumans(),
        ]);

      return response()->json([
        'success' => true,
        'venda' => [
          'id' => $venda->id,
          'nome_contrato' => $venda->nome_contrato,
          'operadora' => $venda->operadora,
        ],
        'anotacoes' => $anotacoes,
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar anotações: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Adiciona nova anotação pós-venda
   */
  public function storeAnotacaoPosVenda(Request $request)
  {
    try {
      $request->validate([
        'venda_id' => 'required|integer|exists:vendas,id',
        'descricao' => 'required|string|max:2000',
      ]);

      $empresaId = Auth::user()->empresa_id;

      $venda = Vendas::where('id', $request->venda_id)
        ->where('empresa_id', $empresaId)
        ->first();

      if (!$venda) {
        return response()->json([
          'success' => false,
          'message' => 'Venda não encontrada.'
        ], 404);
      }

      $anotacao = PosVendaAnotacao::create([
        'empresa_id' => $empresaId,
        'venda_id' => $request->venda_id,
        'user_id' => Auth::id(),
        'descricao' => $request->descricao,
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Anotação salva com sucesso.',
        'anotacao' => [
          'id' => $anotacao->id,
          'descricao' => $anotacao->descricao,
          'usuario' => Auth::user()->name,
          'data' => $anotacao->created_at->format('d/m/Y H:i'),
          'data_relativa' => 'agora',
        ],
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro de validação.',
        'errors' => $e->errors(),
      ], 422);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao salvar anotação: ' . $e->getMessage()
      ], 500);
    }
  }
}
