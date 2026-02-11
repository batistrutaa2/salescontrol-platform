<?php

namespace App\Http\Controllers\pages\financeiro;

use App\Http\Controllers\Controller;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\Recebivel;
use App\Models\RegrasComissionamento;
use App\Models\RegrasComissionamentoParcela;
use App\Models\User;
use App\Models\Vendas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class Financeiro extends Controller
{
    // ------------------------------
    // View principal
    // ------------------------------
    public function regrasRecebimentos()
    {
        $operadoras = Operadora::where('empresa_id', auth()->user()->empresa_id)->get();

        return view('content.pages.financeiro.regras-recebimentos', compact('operadoras'));
    }

    // ------------------------------
    // CRUD Regras
    // ------------------------------
    public function regrasIndex(Request $request)
    {
        if ($request->ajax()) {
            $query = RegrasComissionamento::with('operadoras')->select('regras_comissionamento.*')
                ->where('regras_comissionamento.empresa_id', auth()->user()->empresa_id);

            return DataTables::of($query)
                ->addColumn('operadora_nome', function ($row) {
                    return $row->operadoras->nome ?? '';
                })
                ->make(true);
        }

        return view('content.pages.financeiro.regras-recebimentos');
    }

    public function regrasStore(Request $request)
    {
        $data = $request->validate([
            'operadora_id' => 'required|exists:operadoras,id',
            'categoria' => 'required|in:PME,ADESAO',
            'total_percentual' => 'nullable|numeric',
            'descricao' => 'nullable|string|max:255',
            'vitalicio' => 'boolean',
            'percentual_vitalicio' => 'nullable|numeric|min:0|max:100',
        ]);

        $rule = RegrasComissionamento::create([
            'empresa_id' => auth()->user()->empresa_id,
            'operadora_id' => $data['operadora_id'],
            'categoria' => $data['categoria'],
            'total_percentual' => $data['total_percentual'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'vitalicio' => $data['vitalicio'] ?? 0,
            'percentual_vitalicio' => $data['percentual_vitalicio'] ?? null,
        ]);

        return response()->json($rule);
    }

    public function regrasUpdate(Request $request, $id)
    {
        $rule = RegrasComissionamento::findOrFail($id);

        $data = $request->validate([
            'operadora_id' => 'required|exists:operadoras,id',
            'categoria' => 'required|in:PME,ADESAO',
            'total_percentual' => 'nullable|numeric',
            'descricao' => 'nullable|string|max:255',
            'vitalicio' => 'boolean',
            'percentual_vitalicio' => 'nullable|numeric|min:0|max:100',
        ]);

        $rule->update([
            'operadora_id' => $data['operadora_id'],
            'categoria' => $data['categoria'],
            'total_percentual' => $data['total_percentual'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'vitalicio' => $data['vitalicio'] ?? 0,
            'percentual_vitalicio' => $data['percentual_vitalicio'] ?? null,
        ]);

        return response()->json($rule);
    }

    public function regrasDestroy($id)
    {
        $rule = RegrasComissionamento::findOrFail($id);
        $rule->delete();

        return response()->json(['success' => true]);
    }

    // ------------------------------
    // CRUD Parcelas
    // ------------------------------
    public function parcelasIndex($ruleId)
    {
        return RegrasComissionamentoParcela::where('regra_id', $ruleId)
            ->where('empresa_id', auth()->user()->empresa_id)
            ->get();
    }

    public function parcelasStore(Request $request)
    {
        $data = $request->validate([
            'commission_rule_id' => 'required|exists:regras_comissionamento,id',
            'installment_number' => 'required|integer|min:1',
            'percent' => 'required|numeric|min:0',
            'payer' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
        ]);

        $parcela = RegrasComissionamentoParcela::create([
            'empresa_id' => auth()->user()->empresa_id,
            'regra_id' => $data['commission_rule_id'],
            'parcela' => $data['installment_number'],
            'percentual' => $data['percent'],
            'pagador' => $data['payer'],
        ]);

        return response()->json($parcela);
    }

    public function parcelasUpdate(Request $request, $id)
    {
        $parcela = RegrasComissionamentoParcela::findOrFail($id);

        $data = $request->validate([
            'installment_number' => 'required|integer|min:1',
            'percent' => 'required|numeric|min:0',
            'payer' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
        ]);

        $parcela->update([
            'parcela' => $data['installment_number'],
            'percentual' => $data['percent'],
            'pagador' => $data['payer'],
        ]);

        return response()->json($parcela);
    }

    public function parcelasDestroy($id)
    {
        $parcela = RegrasComissionamentoParcela::findOrFail($id);
        $parcela->delete();

        return response()->json(['success' => true]);
    }

    /**
     * 📑 Listagem geral de recebíveis (otimizado)
     */
    public function indexRecebiveis(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $anoSelecionado = $request->ano;

        // Agrupar contratos via SQL (evita carregar todos os recebíveis)
        $contratosQuery = Recebivel::where('recebiveis.empresa_id', $empresaId)
            ->join('vendas', 'recebiveis.venda_id', '=', 'vendas.id')
            ->join('users', 'recebiveis.vendedor_id', '=', 'users.id')
            ->select([
                'recebiveis.venda_id',
                'recebiveis.operadora',
                'vendas.nome_contrato',
                'vendas.valor_contrato',
                'users.name as vendedor_name',
            ])
            ->selectRaw('SUM(recebiveis.valor) as valor_total')
            ->selectRaw("SUM(CASE WHEN recebiveis.status = 'PAGO' THEN recebiveis.valor ELSE 0 END) as valor_pago")
            ->selectRaw("SUM(CASE WHEN recebiveis.status = 'PENDENTE' AND recebiveis.data_prevista < NOW() THEN 1 ELSE 0 END) as parcelas_atrasadas")
            ->groupBy('recebiveis.venda_id', 'recebiveis.operadora', 'vendas.nome_contrato', 'vendas.valor_contrato', 'users.name');

        if ($anoSelecionado) {
            $contratosQuery->whereYear('vendas.data_implantacao', $anoSelecionado);
        }

        $contratosRaw = $contratosQuery->get();

        // Mapear para o formato esperado pela view
        $contratos = $contratosRaw->mapWithKeys(function ($row) {
            $valorPendente = $row->valor_total - $row->valor_pago;

            return [$row->venda_id => (object) [
                'venda' => (object) [
                    'id' => $row->venda_id,
                    'nome_contrato' => $row->nome_contrato,
                    'valor_contrato' => $row->valor_contrato,
                ],
                'vendedor' => (object) [
                    'name' => $row->vendedor_name,
                ],
                'operadora' => $row->operadora,
                'valor_total' => (float) $row->valor_total,
                'valor_pago' => (float) $row->valor_pago,
                'valor_pendente' => (float) $valorPendente,
                'em_atraso' => $row->parcelas_atrasadas > 0,
            ]];
        });

        // Anos disponíveis (query otimizada)
        $anosDisponiveis = Vendas::whereNotNull('data_implantacao')
            ->where('empresa_id', $empresaId)
            ->whereIn('id', Recebivel::where('empresa_id', $empresaId)->select('venda_id')->distinct())
            ->selectRaw("YEAR(data_implantacao) as ano")
            ->selectRaw('COUNT(*) as total_contratos')
            ->groupBy('ano')
            ->orderBy('ano', 'desc')
            ->get()
            ->toArray();

        return view('content.pages.financeiro.recebiveis', [
            'contratos' => $contratos,
            'anosDisponiveis' => $anosDisponiveis,
            'anoSelecionado' => $anoSelecionado,
        ]);
    }

    /**
     * 🔍 Mostrar todas as parcelas de um contrato específico
     */
    public function showContratoRecebiveis(int $vendaId)
    {
        $venda = Vendas::findOrFail($vendaId);

        $parcelas = Recebivel::where('venda_id', $vendaId)
            ->orderBy('parcela', 'asc')
            ->get();

        // Insights
        $total = $parcelas->sum('valor');
        $totalPago = $parcelas->where('status', 'PAGO')->sum('valor');
        $totalPendente = $parcelas->where('status', 'PENDENTE')->sum('valor');
        $totalCancelado = $parcelas->where('status', 'CANCELADO')->sum('valor');

        return view('financeiro.recebiveis.show', compact(
            'venda',
            'parcelas',
            'total',
            'totalPago',
            'totalPendente',
            'totalCancelado'
        ));
    }

    /**
     * ✅ Marcar parcela como recebida
     */
    public function pagarRecebivel(Request $request, Recebivel $recebivel)
    {
        $recebivel->update([
            'status' => 'PAGO',
            'data_recebimento' => now(),
        ]);

        return back()->with('status', 'success')->with('message', 'Parcela marcada como PAGA.');
    }

    /**
     * ❌ Cancelar parcela
     */
    public function cancelarRecebivel(Request $request, Recebivel $recebivel)
    {
        $recebivel->update([
            'status' => 'CANCELADO',
        ]);

        return back()->with('status', 'warning')->with('message', 'Parcela CANCELADA.');
    }

    public function getParcelas($vendaId)
    {
        $venda = Vendas::find($vendaId);

        // Buscar percentual vitalício da regra de comissionamento
        $percentualVitalicio = null;
        if ($venda) {
            $operadora = Operadora::where('nome', $venda->operadora)->first();
            if ($operadora) {
                $regra = RegrasComissionamento::where('empresa_id', $venda->empresa_id)
                    ->where('operadora_id', $operadora->id)
                    ->first();
                if ($regra && $regra->percentual_vitalicio && $regra->percentual_vitalicio > 0) {
                    $percentualVitalicio = $regra->percentual_vitalicio;
                }
            }
        }

        $parcelas = Recebivel::where('venda_id', $vendaId)
            ->orderBy('parcela')
            ->get()
            ->map(function ($p) use ($percentualVitalicio) {
                $custoAtual = null;
                if ($p->parcela >= 4 && $percentualVitalicio) {
                    $custoAtual = $p->valor / ($percentualVitalicio / 100);
                }

                return [
                    'id' => $p->id,
                    'parcela' => $p->parcela,
                    'valor' => $p->valor,
                    'custo_atual' => $custoAtual,
                    'data_prevista' => \Carbon\Carbon::parse($p->data_prevista)->format('d/m/Y'),
                    'data_recebimento' => $p->data_recebimento ? \Carbon\Carbon::parse($p->data_recebimento)->format('d/m/Y') : null,
                    'status' => $p->status,
                    'tipo' => $p->parcela >= 4 ? 'vitalicio' : 'parcela',
                ];
            });

        return response()->json($parcelas);
    }

    public function pagarParcela($id)
    {
        $parcela = Recebivel::findOrFail($id);
        $parcela->update(['status' => 'PAGO', 'data_recebimento' => now()]);

        // Verificar se deve gerar próxima parcela vitalícia
        $novaParcelaGerada = $this->gerarProximaParcelaVitalicia($parcela);

        return response()->json([
            'success' => true,
            'nova_parcela_gerada' => $novaParcelaGerada,
        ]);
    }

    /**
     * Gera a próxima parcela vitalícia se a regra permitir
     */
    private function gerarProximaParcelaVitalicia(Recebivel $parcelaPaga): bool
    {
        $venda = Vendas::find($parcelaPaga->venda_id);
        if (! $venda) {
            return false;
        }

        // Buscar operadora
        $operadora = Operadora::where('nome', $venda->operadora)->first();
        if (! $operadora) {
            return false;
        }

        // Buscar regra de comissionamento
        $regra = RegrasComissionamento::with('parcelas')
            ->where('empresa_id', $venda->empresa_id)
            ->where('operadora_id', $operadora->id)
            ->first();

        if (! $regra || ! $regra->vitalicio || ! $regra->percentual_vitalicio || $regra->percentual_vitalicio <= 0) {
            return false;
        }

        // Verificar qual a última parcela normal da regra
        $ultimaParcelaNormal = $regra->parcelas()->max('parcela') ?? 0;

        // Verificar a última parcela existente para esta venda
        $ultimaParcelaExistente = Recebivel::where('venda_id', $venda->id)->max('parcela') ?? 0;

        // Se a parcela paga é a última existente, gerar a próxima
        if ($parcelaPaga->parcela == $ultimaParcelaExistente) {
            $proximaParcela = $ultimaParcelaExistente + 1;

            // Resolver nome do plano
            $planoNome = 'N/A';
            if (! empty($venda->plano_id)) {
                $plano = Plano::find($venda->plano_id);
                $planoNome = $plano?->nome ?? $venda->nome_plano ?? 'N/A';
            } elseif (! empty($venda->nome_plano)) {
                $planoNome = $venda->nome_plano;
            }

            $valorVitalicio = ($regra->percentual_vitalicio / 100) * $venda->valor_contrato;

            Recebivel::create([
                'empresa_id' => $venda->empresa_id,
                'venda_id' => $venda->id,
                'vendedor_id' => $venda->user_id,
                'operadora' => $operadora->nome,
                'plano' => $planoNome,
                'parcela' => $proximaParcela,
                'valor' => $valorVitalicio,
                'data_prevista' => Carbon::parse($venda->data_implantacao)
                    ->addMonths($proximaParcela - 1),
                'status' => 'PENDENTE',
            ]);

            Log::info("Gerada parcela vitalícia #{$proximaParcela} para venda {$venda->id}");

            return true;
        }

        return false;
    }

    /**
     * Atualiza a data de recebimento e/ou valor de uma parcela (para parcelas já pagas ou para registrar pagamento retroativo)
     */
    public function atualizarDataRecebimento(Request $request, $id)
    {
        $request->validate([
            'data_recebimento' => 'required|date',
            'valor' => 'nullable|numeric|min:0',
        ]);

        $parcela = Recebivel::findOrFail($id);

        // Verificar se pertence à empresa do usuário
        if ($parcela->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
            ], 403);
        }

        // Verificar se parcela estava pendente (para gerar próxima vitalícia)
        $estavaPendente = $parcela->status === 'PENDENTE';

        $dataRecebimento = Carbon::parse($request->data_recebimento);

        $updateData = [
            'status' => 'PAGO',
            'data_recebimento' => $dataRecebimento,
        ];

        // Se o valor foi informado, atualiza também
        if ($request->filled('valor')) {
            $updateData['valor'] = $request->valor;
        }

        $parcela->update($updateData);

        // Se estava pendente, verificar se deve gerar próxima parcela vitalícia
        $novaParcelaGerada = false;
        if ($estavaPendente) {
            $novaParcelaGerada = $this->gerarProximaParcelaVitalicia($parcela);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parcela atualizada com sucesso.',
            'data_recebimento' => $dataRecebimento->format('d/m/Y'),
            'valor' => number_format($parcela->valor, 2, ',', '.'),
            'nova_parcela_gerada' => $novaParcelaGerada,
        ]);
    }

    /**
     * Excluir uma parcela específica
     */
    public function excluirParcela($id)
    {
        $parcela = Recebivel::findOrFail($id);

        // Verificar permissão (empresa_id)
        if ($parcela->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
            ], 403);
        }

        $numeroParcela = $parcela->parcela;
        $vendaId = $parcela->venda_id;

        $parcela->delete();

        Log::info("Parcela #{$numeroParcela} excluída da venda {$vendaId} pelo usuário ".auth()->user()->id);

        return response()->json([
            'success' => true,
            'message' => "Parcela #{$numeroParcela} excluída com sucesso.",
        ]);
    }

    /**
     * Excluir múltiplas parcelas de uma vez
     */
    public function excluirMultiplasParcelas(Request $request)
    {
        $request->validate([
            'parcela_ids' => 'required|array|min:1',
            'parcela_ids.*' => 'integer|exists:recebiveis,id',
        ]);

        $parcelaIds = $request->parcela_ids;
        $empresaId = auth()->user()->empresa_id;

        // Verificar se todas as parcelas pertencem à empresa do usuário
        $parcelas = Recebivel::whereIn('id', $parcelaIds)
            ->where('empresa_id', $empresaId)
            ->get();

        if ($parcelas->count() !== count($parcelaIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Algumas parcelas não foram encontradas ou você não tem permissão para excluí-las.',
            ], 403);
        }

        // Pegar o venda_id para retornar (assumindo que todas são do mesmo contrato)
        $vendaId = $parcelas->first()->venda_id;

        // Excluir as parcelas
        $totalExcluido = Recebivel::whereIn('id', $parcelaIds)
            ->where('empresa_id', $empresaId)
            ->delete();

        Log::info("Excluídas {$totalExcluido} parcelas da venda {$vendaId} pelo usuário " . auth()->user()->id);

        return response()->json([
            'success' => true,
            'message' => "{$totalExcluido} parcela(s) excluída(s) com sucesso.",
            'total_excluido' => $totalExcluido,
            'venda_id' => $vendaId,
        ]);
    }

    /**
     * Dar baixa em múltiplas parcelas de uma vez
     * - Muda status de PENDENTE para PAGO
     * - A data de recebimento será igual à data de vencimento (data_prevista)
     */
    public function darBaixaMultiplasParcelas(Request $request)
    {
        $request->validate([
            'parcela_ids' => 'required|array|min:1',
            'parcela_ids.*' => 'integer|exists:recebiveis,id',
        ]);

        $parcelaIds = $request->parcela_ids;
        $empresaId = auth()->user()->empresa_id;

        $parcelas = Recebivel::whereIn('id', $parcelaIds)
            ->where('empresa_id', $empresaId)
            ->where('status', 'PENDENTE')
            ->orderBy('parcela')
            ->get();

        if ($parcelas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma parcela pendente encontrada entre as selecionadas.',
            ], 422);
        }

        $totalBaixa = 0;
        $parcelasVitalicias = collect();

        foreach ($parcelas as $parcela) {
            $parcela->update([
                'status' => 'PAGO',
                'data_recebimento' => $parcela->data_prevista,
            ]);
            $totalBaixa++;

            $novaGerada = $this->gerarProximaParcelaVitalicia($parcela);
            if ($novaGerada) {
                $parcelasVitalicias->push($parcela->parcela);
            }
        }

        $vendaId = $parcelas->first()->venda_id;

        Log::info("Baixa em {$totalBaixa} parcelas da venda {$vendaId} pelo usuário " . auth()->user()->id);

        $message = "{$totalBaixa} parcela(s) marcada(s) como paga(s) com sucesso.";
        if ($parcelasVitalicias->isNotEmpty()) {
            $message .= " Nova(s) parcela(s) vitalícia(s) gerada(s).";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'total_baixa' => $totalBaixa,
            'venda_id' => $vendaId,
            'parcelas_vitalicias_geradas' => $parcelasVitalicias->count(),
        ]);
    }

    /**
     * Editar múltiplas parcelas de uma vez
     * - O valor é replicado para todas as parcelas selecionadas
     * - A data de pagamento é incrementada mês a mês para cada parcela (ordenadas por número)
     */
    public function editarMultiplasParcelas(Request $request)
    {
        $request->validate([
            'parcela_ids' => 'required|array|min:1',
            'parcela_ids.*' => 'integer|exists:recebiveis,id',
            'valor' => 'required|numeric|min:0',
            'data_pagamento' => 'required|date',
        ]);

        $parcelaIds = $request->parcela_ids;
        $valor = $request->valor;
        $dataPagamentoBase = Carbon::parse($request->data_pagamento);
        $empresaId = auth()->user()->empresa_id;

        // Verificar se todas as parcelas pertencem à empresa do usuário
        $parcelas = Recebivel::whereIn('id', $parcelaIds)
            ->where('empresa_id', $empresaId)
            ->orderBy('parcela', 'asc') // Ordenar por número da parcela
            ->get();

        if ($parcelas->count() !== count($parcelaIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Algumas parcelas não foram encontradas ou você não tem permissão para editá-las.',
            ], 403);
        }

        // Pegar o venda_id para retornar
        $vendaId = $parcelas->first()->venda_id;

        // Atualizar cada parcela
        $totalEditado = 0;
        foreach ($parcelas as $index => $parcela) {
            // Calcula a data de pagamento para esta parcela (incrementa mês a mês)
            $dataPagamento = $dataPagamentoBase->copy()->addMonths($index);

            $parcela->update([
                'valor' => $valor,
                'data_prevista' => $dataPagamento->format('Y-m-d'),
            ]);

            $totalEditado++;
        }

        Log::info("Editadas {$totalEditado} parcelas da venda {$vendaId} pelo usuário " . auth()->user()->id . " - Valor: {$valor}");

        return response()->json([
            'success' => true,
            'message' => "{$totalEditado} parcela(s) editada(s) com sucesso.",
            'total_editado' => $totalEditado,
            'venda_id' => $vendaId,
        ]);
    }

    /**
     * Excluir todos os recebíveis de uma venda
     */
    public function excluirTodosRecebiveis(int $vendaId)
    {
        $venda = Vendas::findOrFail($vendaId);

        // Verificar permissão (empresa_id)
        if ($venda->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
            ], 403);
        }

        $totalExcluido = Recebivel::where('venda_id', $vendaId)->count();

        if ($totalExcluido === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum recebível encontrado para este contrato.',
            ], 404);
        }

        Recebivel::where('venda_id', $vendaId)->delete();

        Log::info("Todos os recebíveis ({$totalExcluido}) da venda {$vendaId} foram excluídos pelo usuário ".auth()->user()->id);

        return response()->json([
            'success' => true,
            'message' => "{$totalExcluido} parcela(s) excluída(s) com sucesso.",
        ]);
    }

    public function relatorioFinanceiro()
    {
        $empresaId = auth()->user()->empresa_id;
        $operadoras = Operadora::where('empresa_id', $empresaId)->orderBy('nome')->get();
        $vendedores = User::where('empresa_id', $empresaId)->where('ativo', 'Y')->orderBy('name')->get();

        return view('content.pages.financeiro.relatorio-financeiro', compact('operadoras', 'vendedores'));
    }

    public function relatorioFinanceiroFetch(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        // ── Filtros ──
        $dataInicial = $request->input('data_inicial');
        $dataFinal = $request->input('data_final');
        $operadoraId = $request->input('operadora_id');
        $vendedorId = $request->input('vendedor_id');
        $tipoReceita = $request->input('tipo_receita', 'todas'); // todas|fixa|vitalicio

        $operadoraNome = null;
        if ($operadoraId) {
            $operadoraNome = Operadora::find($operadoraId)?->nome;
        }

        // Closure para aplicar filtros comuns
        $baseFilter = function ($query) use ($empresaId, $dataInicial, $dataFinal, $operadoraNome, $vendedorId, $tipoReceita) {
            $query->where('empresa_id', $empresaId);
            if ($dataInicial) {
                $query->whereDate('data_prevista', '>=', $dataInicial);
            }
            if ($dataFinal) {
                $query->whereDate('data_prevista', '<=', $dataFinal);
            }
            if ($operadoraNome) {
                $query->where('operadora', $operadoraNome);
            }
            if ($vendedorId) {
                $query->where('vendedor_id', $vendedorId);
            }
            if ($tipoReceita === 'fixa') {
                $query->where('parcela', '<=', 3);
            } elseif ($tipoReceita === 'vitalicio') {
                $query->where('parcela', '>=', 4);
            }
        };

        // ══════════════════════════════════════════
        // 1. KPIs — query única com CASE WHEN
        // ══════════════════════════════════════════
        $kpiQuery = Recebivel::query();
        $baseFilter($kpiQuery);

        $kpis = $kpiQuery->selectRaw("
            SUM(CASE WHEN status IN ('PAGO','PENDENTE') THEN valor ELSE 0 END) as receita_total,
            SUM(CASE WHEN status = 'PAGO' THEN valor ELSE 0 END) as receita_recebida,
            SUM(CASE WHEN status = 'PENDENTE' AND data_prevista < NOW() THEN valor ELSE 0 END) as inadimplencia,
            SUM(CASE WHEN status = 'PENDENTE' AND data_prevista < NOW() THEN 1 ELSE 0 END) as inadimplencia_qtd,
            SUM(CASE WHEN parcela <= 3 AND status = 'PAGO' THEN valor ELSE 0 END) as receita_fixa,
            SUM(CASE WHEN parcela >= 4 AND status = 'PAGO' THEN valor ELSE 0 END) as receita_vitalicio,
            SUM(CASE WHEN status = 'CANCELADO' THEN valor ELSE 0 END) as receita_cancelada,
            SUM(valor) as total_geral
        ")->first();

        $receitaTotal = (float) ($kpis->receita_total ?? 0);
        $receitaRecebida = (float) ($kpis->receita_recebida ?? 0);
        $inadimplencia = (float) ($kpis->inadimplencia ?? 0);
        $inadimplenciaQtd = (int) ($kpis->inadimplencia_qtd ?? 0);
        $receitaFixa = (float) ($kpis->receita_fixa ?? 0);
        $receitaVitalicio = (float) ($kpis->receita_vitalicio ?? 0);
        $receitaCancelada = (float) ($kpis->receita_cancelada ?? 0);
        $totalGeral = (float) ($kpis->total_geral ?? 0);

        $taxaRecebimento = $receitaTotal > 0 ? round(($receitaRecebida / $receitaTotal) * 100, 2) : 0;
        $receitaVitalicioPercentual = $receitaRecebida > 0 ? round(($receitaVitalicio / $receitaRecebida) * 100, 1) : 0;
        $taxaCancelamento = $totalGeral > 0 ? round(($receitaCancelada / $totalGeral) * 100, 1) : 0;

        // ── MRR (últimos 3 meses, parcela>=4, PAGO) — ignora filtro de data ──
        $mrrBaseFilter = function ($query) use ($empresaId, $operadoraNome, $vendedorId) {
            $query->where('empresa_id', $empresaId);
            if ($operadoraNome) {
                $query->where('operadora', $operadoraNome);
            }
            if ($vendedorId) {
                $query->where('vendedor_id', $vendedorId);
            }
        };

        $mrr3Meses = Recebivel::query()->tap($mrrBaseFilter)
            ->where('parcela', '>=', 4)
            ->where('status', 'PAGO')
            ->where('data_recebimento', '>=', Carbon::now()->subMonths(3)->startOfMonth())
            ->sum('valor');
        $mrr = round((float) $mrr3Meses / 3, 2);

        $mrrAnterior3Meses = Recebivel::query()->tap($mrrBaseFilter)
            ->where('parcela', '>=', 4)
            ->where('status', 'PAGO')
            ->where('data_recebimento', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->where('data_recebimento', '<', Carbon::now()->subMonths(3)->startOfMonth())
            ->sum('valor');
        $mrrAnterior = round((float) $mrrAnterior3Meses / 3, 2);
        $mrrVariacao = $mrrAnterior > 0 ? round((($mrr - $mrrAnterior) / $mrrAnterior) * 100, 1) : 0;

        // ── Contratos ativos (DISTINCT venda_id com parcela PENDENTE) ──
        $contratosQuery = Recebivel::query();
        $baseFilter($contratosQuery);
        $contratosAtivos = (int) $contratosQuery->where('status', 'PENDENTE')
            ->distinct('venda_id')
            ->count('venda_id');

        // Valor da carteira
        $vendaIds = Recebivel::query()->tap(function ($q) use ($empresaId, $operadoraNome, $vendedorId, $tipoReceita) {
            $q->where('empresa_id', $empresaId);
            if ($operadoraNome) {
                $q->where('operadora', $operadoraNome);
            }
            if ($vendedorId) {
                $q->where('vendedor_id', $vendedorId);
            }
            if ($tipoReceita === 'fixa') {
                $q->where('parcela', '<=', 3);
            } elseif ($tipoReceita === 'vitalicio') {
                $q->where('parcela', '>=', 4);
            }
        })->where('status', 'PENDENTE')->distinct()->pluck('venda_id');

        $valorCarteira = $vendaIds->isNotEmpty()
            ? (float) Vendas::whereIn('id', $vendaIds)->sum('valor_contrato')
            : 0;

        // ── Variação anual (comparar mesmo período ano anterior) ──
        $variacaoAnual = 0;
        if ($dataInicial && $dataFinal) {
            $anoAnteriorInicio = Carbon::parse($dataInicial)->subYear()->format('Y-m-d');
            $anoAnteriorFim = Carbon::parse($dataFinal)->subYear()->format('Y-m-d');
            $receitaAnoAnterior = (float) Recebivel::where('empresa_id', $empresaId)
                ->whereDate('data_prevista', '>=', $anoAnteriorInicio)
                ->whereDate('data_prevista', '<=', $anoAnteriorFim)
                ->where('status', 'PAGO')
                ->when($operadoraNome, fn ($q) => $q->where('operadora', $operadoraNome))
                ->when($vendedorId, fn ($q) => $q->where('vendedor_id', $vendedorId))
                ->sum('valor');
            $variacaoAnual = $receitaAnoAnterior > 0
                ? round((($receitaRecebida - $receitaAnoAnterior) / $receitaAnoAnterior) * 100, 1)
                : 0;
        }

        // ══════════════════════════════════════════
        // 2. Evolução Mensal (Receita Fixa vs Vitalício)
        // ══════════════════════════════════════════
        $evolucaoQuery = Recebivel::query();
        $baseFilter($evolucaoQuery);

        $evolucaoMensal = $evolucaoQuery->where('status', 'PAGO')
            ->selectRaw("
                DATE_FORMAT(data_prevista, '%Y-%m') as mes,
                SUM(CASE WHEN parcela <= 3 THEN valor ELSE 0 END) as receita_fixa,
                SUM(CASE WHEN parcela >= 4 THEN valor ELSE 0 END) as receita_vitalicio,
                SUM(valor) as total
            ")
            ->groupByRaw("DATE_FORMAT(data_prevista, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(data_prevista, '%Y-%m')")
            ->get()
            ->map(fn ($row) => [
                'mes' => Carbon::parse($row->mes.'-01')->format('m/Y'),
                'receita_fixa' => (float) $row->receita_fixa,
                'receita_vitalicio' => (float) $row->receita_vitalicio,
                'total' => (float) $row->total,
            ]);

        // ══════════════════════════════════════════
        // 3. Taxa de Recebimento (Gauge)
        // ══════════════════════════════════════════
        $gaugeQuery = Recebivel::query();
        $baseFilter($gaugeQuery);
        $gauge = $gaugeQuery->selectRaw("
            SUM(CASE WHEN status IN ('PAGO','PENDENTE') THEN valor ELSE 0 END) as total_previsto,
            SUM(CASE WHEN status = 'PAGO' THEN valor ELSE 0 END) as total_recebido,
            SUM(CASE WHEN status = 'PENDENTE' THEN valor ELSE 0 END) as total_aberto
        ")->first();

        $taxaRecebimentoGauge = [
            'total_previsto' => (float) ($gauge->total_previsto ?? 0),
            'total_recebido' => (float) ($gauge->total_recebido ?? 0),
            'total_aberto' => (float) ($gauge->total_aberto ?? 0),
            'percentual' => ($gauge->total_previsto ?? 0) > 0
                ? round(((float) $gauge->total_recebido / (float) $gauge->total_previsto) * 100, 1)
                : 0,
        ];

        // ══════════════════════════════════════════
        // 4. Receita por Operadora (top 10)
        // ══════════════════════════════════════════
        $opQuery = Recebivel::query();
        $baseFilter($opQuery);
        $porOperadora = $opQuery->selectRaw("
            operadora,
            SUM(CASE WHEN status = 'PAGO' THEN valor ELSE 0 END) as recebido,
            SUM(CASE WHEN status = 'PENDENTE' THEN valor ELSE 0 END) as pendente,
            SUM(CASE WHEN status = 'CANCELADO' THEN valor ELSE 0 END) as cancelado,
            SUM(valor) as previsto,
            COUNT(CASE WHEN status = 'PAGO' THEN 1 END) as qtd_pago,
            AVG(CASE WHEN status = 'PAGO' THEN valor END) as ticket_medio
        ")
            ->groupBy('operadora')
            ->orderByRaw("SUM(CASE WHEN status = 'PAGO' THEN valor ELSE 0 END) DESC")
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'operadora' => $row->operadora ?: 'Não informado',
                'recebido' => (float) $row->recebido,
                'pendente' => (float) $row->pendente,
                'cancelado' => (float) $row->cancelado,
                'previsto' => (float) $row->previsto,
                'ticket_medio' => round((float) ($row->ticket_medio ?? 0), 2),
                'taxa_recebimento' => $row->previsto > 0 ? round(($row->recebido / $row->previsto) * 100, 1) : 0,
            ]);

        // ══════════════════════════════════════════
        // 6. Comparativo Anual (YoY) — últimos 3 anos
        // ══════════════════════════════════════════
        $anoAtual = (int) Carbon::now()->format('Y');
        $anosYoy = [$anoAtual, $anoAtual - 1, $anoAtual - 2];

        $yoyQuery = Recebivel::where('empresa_id', $empresaId)
            ->where('status', 'PAGO')
            ->whereIn(DB::raw('YEAR(data_recebimento)'), $anosYoy)
            ->when($operadoraNome, fn ($q) => $q->where('operadora', $operadoraNome))
            ->when($vendedorId, fn ($q) => $q->where('vendedor_id', $vendedorId))
            ->when($tipoReceita === 'fixa', fn ($q) => $q->where('parcela', '<=', 3))
            ->when($tipoReceita === 'vitalicio', fn ($q) => $q->where('parcela', '>=', 4))
            ->selectRaw("YEAR(data_recebimento) as ano, MONTH(data_recebimento) as mes, SUM(valor) as total")
            ->groupByRaw('YEAR(data_recebimento), MONTH(data_recebimento)')
            ->get();

        $comparativoAnual = ['anos' => $anosYoy, 'series' => []];
        foreach ($anosYoy as $ano) {
            $meses = array_fill(0, 12, 0);
            foreach ($yoyQuery->where('ano', $ano) as $row) {
                $meses[$row->mes - 1] = (float) $row->total;
            }
            $comparativoAnual['series'][$ano] = $meses;
        }

        // ══════════════════════════════════════════
        // 7. Previsão de Receita (Histórico + Futuro)
        // ══════════════════════════════════════════
        $historico = Recebivel::where('empresa_id', $empresaId)
            ->where('status', 'PAGO')
            ->where('data_recebimento', '>=', Carbon::now()->subMonths(12)->startOfMonth())
            ->when($operadoraNome, fn ($q) => $q->where('operadora', $operadoraNome))
            ->when($vendedorId, fn ($q) => $q->where('vendedor_id', $vendedorId))
            ->when($tipoReceita === 'fixa', fn ($q) => $q->where('parcela', '<=', 3))
            ->when($tipoReceita === 'vitalicio', fn ($q) => $q->where('parcela', '>=', 4))
            ->selectRaw("DATE_FORMAT(data_recebimento, '%Y-%m') as mes, SUM(valor) as valor")
            ->groupByRaw("DATE_FORMAT(data_recebimento, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(data_recebimento, '%Y-%m')")
            ->get()
            ->map(fn ($r) => ['mes' => Carbon::parse($r->mes.'-01')->format('m/Y'), 'valor' => (float) $r->valor]);

        $previsao = Recebivel::where('empresa_id', $empresaId)
            ->where('status', 'PENDENTE')
            ->where('data_prevista', '>=', Carbon::now()->startOfMonth())
            ->where('data_prevista', '<=', Carbon::now()->addMonths(6)->endOfMonth())
            ->when($operadoraNome, fn ($q) => $q->where('operadora', $operadoraNome))
            ->when($vendedorId, fn ($q) => $q->where('vendedor_id', $vendedorId))
            ->when($tipoReceita === 'fixa', fn ($q) => $q->where('parcela', '<=', 3))
            ->when($tipoReceita === 'vitalicio', fn ($q) => $q->where('parcela', '>=', 4))
            ->selectRaw("DATE_FORMAT(data_prevista, '%Y-%m') as mes, SUM(valor) as valor")
            ->groupByRaw("DATE_FORMAT(data_prevista, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(data_prevista, '%Y-%m')")
            ->get()
            ->map(fn ($r) => ['mes' => Carbon::parse($r->mes.'-01')->format('m/Y'), 'valor' => (float) $r->valor]);

        $previsaoReceita = [
            'historico' => $historico->values(),
            'previsao' => $previsao->values(),
        ];

        // ══════════════════════════════════════════
        // 9. Análise de Coorte por Ano de Implantação
        // ══════════════════════════════════════════
        $cohort = Recebivel::where('recebiveis.empresa_id', $empresaId)
            ->join('vendas', 'recebiveis.venda_id', '=', 'vendas.id')
            ->when($operadoraNome, fn ($q) => $q->where('recebiveis.operadora', $operadoraNome))
            ->when($vendedorId, fn ($q) => $q->where('recebiveis.vendedor_id', $vendedorId))
            ->selectRaw("
                YEAR(vendas.data_implantacao) as ano_implantacao,
                COUNT(DISTINCT recebiveis.venda_id) as qtd_contratos,
                SUM(CASE WHEN recebiveis.parcela <= 3 AND recebiveis.status = 'PAGO' THEN recebiveis.valor ELSE 0 END) as receita_fixa,
                SUM(CASE WHEN recebiveis.parcela >= 4 AND recebiveis.status = 'PAGO' THEN recebiveis.valor ELSE 0 END) as receita_vitalicio,
                SUM(CASE WHEN recebiveis.status = 'PAGO' THEN recebiveis.valor ELSE 0 END) as receita_total
            ")
            ->groupByRaw('YEAR(vendas.data_implantacao)')
            ->orderByRaw('YEAR(vendas.data_implantacao) DESC')
            ->get();

        // Buscar valor_contrato separado para evitar duplicação pelo join
        $cohortAnalise = $cohort->map(function ($row) use ($empresaId) {
            $valorContratos = (float) Vendas::where('empresa_id', $empresaId)
                ->whereYear('data_implantacao', $row->ano_implantacao)
                ->sum('valor_contrato');

            $receitaTotal = (float) $row->receita_total;

            return [
                'ano_implantacao' => $row->ano_implantacao ?? 'N/D',
                'qtd_contratos' => (int) $row->qtd_contratos,
                'valor_total_contratos' => $valorContratos,
                'receita_fixa' => (float) $row->receita_fixa,
                'receita_vitalicio' => (float) $row->receita_vitalicio,
                'receita_total' => $receitaTotal,
                'roi' => $valorContratos > 0 ? round(($receitaTotal / $valorContratos) * 100, 1) : 0,
            ];
        })->values();

        // ══════════════════════════════════════════
        // Response
        // ══════════════════════════════════════════
        return response()->json([
            'kpis' => [
                'receita_total' => $receitaTotal,
                'receita_recebida' => $receitaRecebida,
                'mrr' => $mrr,
                'mrr_variacao' => $mrrVariacao,
                'inadimplencia' => $inadimplencia,
                'inadimplencia_qtd' => $inadimplenciaQtd,
                'receita_fixa' => $receitaFixa,
                'receita_vitalicio' => $receitaVitalicio,
                'receita_vitalicio_percentual' => $receitaVitalicioPercentual,
                'contratos_ativos' => $contratosAtivos,
                'valor_carteira' => $valorCarteira,
                'receita_cancelada' => $receitaCancelada,
                'taxa_cancelamento' => $taxaCancelamento,
                'taxa_recebimento' => $taxaRecebimento,
                'variacao_anual' => $variacaoAnual,
            ],
            'evolucaoMensal' => $evolucaoMensal->values(),
            'taxaRecebimentoGauge' => $taxaRecebimentoGauge,
            'porOperadora' => $porOperadora->values(),
            'comparativoAnual' => $comparativoAnual,
            'previsaoReceita' => $previsaoReceita,
            'cohortAnalise' => $cohortAnalise,
        ]);
    }

    /**
     * Retorna o resumo atualizado de um contrato específico (para atualização via AJAX) - Otimizado
     */
    public function getContratoResumo(int $vendaId)
    {
        $empresaId = auth()->user()->empresa_id;

        // Verificar permissão e calcular tudo em uma única query
        $resumo = Recebivel::where('venda_id', $vendaId)
            ->where('empresa_id', $empresaId)
            ->selectRaw("
                SUM(valor) as valor_total,
                SUM(CASE WHEN status = 'PAGO' THEN valor ELSE 0 END) as valor_pago,
                SUM(CASE WHEN status = 'PENDENTE' AND data_prevista < NOW() THEN 1 ELSE 0 END) as parcelas_atrasadas,
                COUNT(*) as total_parcelas
            ")
            ->first();

        // Se não há parcelas, o contrato foi removido da lista
        if (! $resumo || $resumo->total_parcelas == 0) {
            return response()->json([
                'success' => true,
                'removido' => true,
                'message' => 'Contrato não possui mais recebíveis.',
            ]);
        }

        $valorTotal = (float) $resumo->valor_total;
        $valorPago = (float) $resumo->valor_pago;
        $valorPendente = $valorTotal - $valorPago;
        $emAtraso = $resumo->parcelas_atrasadas > 0;

        // Determinar status do contrato
        if ($valorPendente <= 0) {
            $status = 'Quitado';
            $statusClass = 'status-success';
        } elseif ($emAtraso) {
            $status = 'Atrasado';
            $statusClass = 'status-danger';
        } else {
            $status = 'Pendente';
            $statusClass = 'status-warning';
        }

        return response()->json([
            'success' => true,
            'removido' => false,
            'dados' => [
                'valor_total' => $valorTotal,
                'valor_pago' => $valorPago,
                'valor_pendente' => $valorPendente,
                'em_atraso' => $emAtraso,
                'status' => $status,
                'status_class' => $statusClass,
            ],
        ]);
    }

    /**
     * Recalcular valores dos recebíveis com a regra atual
     * Mantém: status, data_prevista, data_recebimento
     * Atualiza: valor (baseado na nova regra)
     * Cria: novas parcelas se a regra tiver mais parcelas que as existentes
     */
    public function recalcularRecebiveis(int $vendaId)
    {
        $venda = Vendas::findOrFail($vendaId);

        // Verificar permissão (empresa_id)
        if ($venda->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'Sem permissão para acessar este contrato.',
            ], 403);
        }

        // Buscar operadora pelo nome
        $operadora = Operadora::where('nome', $venda->operadora)->first();

        if (! $operadora) {
            return response()->json([
                'success' => false,
                'message' => "Operadora '{$venda->operadora}' não encontrada.",
            ], 404);
        }

        // Buscar regra atual de comissionamento
        $regra = RegrasComissionamento::with('parcelas')
            ->where('empresa_id', $venda->empresa_id)
            ->where('operadora_id', $operadora->id)
            ->first();

        if (! $regra) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma regra de comissionamento encontrada para esta operadora.',
            ], 404);
        }

        // Buscar parcelas da regra
        $parcelasRegra = $regra->parcelas()->orderBy('parcela')->get()->keyBy('parcela');

        if ($parcelasRegra->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'A regra de comissionamento não possui parcelas configuradas.',
            ], 404);
        }

        // Buscar recebíveis atuais do contrato (indexados por número da parcela)
        $recebiveisAtuais = Recebivel::where('venda_id', $vendaId)
            ->orderBy('parcela')
            ->get()
            ->keyBy('parcela');

        // Resolver nome do plano
        $planoNome = 'N/A';
        if (! empty($venda->plano_id)) {
            $plano = Plano::find($venda->plano_id);
            $planoNome = $plano?->nome ?? $venda->nome_plano ?? 'N/A';
        } elseif (! empty($venda->nome_plano)) {
            $planoNome = $venda->nome_plano;
        }

        $alteracoes = [];
        $novasParcelas = [];

        DB::beginTransaction();
        try {
            // Iterar sobre todas as parcelas da regra atual
            foreach ($parcelasRegra as $numeroParcela => $parcelaRegra) {
                $valorNovo = ($parcelaRegra->percentual / 100) * $venda->valor_contrato;

                if ($recebiveisAtuais->has($numeroParcela)) {
                    // Parcela já existe - atualizar valor se diferente
                    $recebivel = $recebiveisAtuais->get($numeroParcela);
                    $valorAntigo = $recebivel->valor;

                    if (abs($valorAntigo - $valorNovo) > 0.01) {
                        $recebivel->update(['valor' => $valorNovo]);

                        $alteracoes[] = [
                            'parcela' => $numeroParcela,
                            'acao' => 'atualizado',
                            'valor_antigo' => $valorAntigo,
                            'valor_novo' => $valorNovo,
                            'diferenca' => $valorNovo - $valorAntigo,
                            'status' => $recebivel->status,
                        ];
                    }
                } else {
                    // Parcela não existe - criar nova
                    $dataPrevista = \Carbon\Carbon::parse($venda->data_implantacao)
                        ->addMonths($numeroParcela);

                    Recebivel::create([
                        'empresa_id' => $venda->empresa_id,
                        'venda_id' => $venda->id,
                        'vendedor_id' => $venda->user_id,
                        'operadora' => $operadora->nome,
                        'plano' => $planoNome,
                        'parcela' => $numeroParcela,
                        'valor' => $valorNovo,
                        'data_prevista' => $dataPrevista,
                        'status' => 'PENDENTE',
                    ]);

                    $novasParcelas[] = [
                        'parcela' => $numeroParcela,
                        'acao' => 'criado',
                        'valor_novo' => $valorNovo,
                        'data_prevista' => $dataPrevista->format('d/m/Y'),
                    ];
                }
            }

            DB::commit();

            // Calcular totais
            $totalAnteriorAlteracoes = collect($alteracoes)->sum('valor_antigo');
            $totalNovoAlteracoes = collect($alteracoes)->sum('valor_novo');
            $totalNovasParcelas = collect($novasParcelas)->sum('valor_novo');

            $totalAlteracoes = count($alteracoes) + count($novasParcelas);

            // Montar mensagem
            $mensagens = [];
            if (count($alteracoes) > 0) {
                $mensagens[] = count($alteracoes).' parcela(s) atualizada(s)';
            }
            if (count($novasParcelas) > 0) {
                $mensagens[] = count($novasParcelas).' parcela(s) criada(s)';
            }

            return response()->json([
                'success' => true,
                'message' => $totalAlteracoes > 0
                    ? 'Recebíveis atualizados: '.implode(', ', $mensagens).'.'
                    : 'Nenhuma alteração necessária. Os valores já estão atualizados.',
                'alteracoes' => $alteracoes,
                'novas_parcelas' => $novasParcelas,
                'resumo' => [
                    'parcelas_atualizadas' => count($alteracoes),
                    'parcelas_criadas' => count($novasParcelas),
                    'total_anterior' => $totalAnteriorAlteracoes,
                    'total_novo' => $totalNovoAlteracoes,
                    'diferenca_atualizacoes' => $totalNovoAlteracoes - $totalAnteriorAlteracoes,
                    'total_novas_parcelas' => $totalNovasParcelas,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao recalcular recebíveis', [
                'venda_id' => $vendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao recalcular valores: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gera recebíveis manualmente para um contrato
     */
    public function gerarRecebiveisManuais(Request $request, int $vendaId)
    {
        $request->validate([
            'quantidade_parcelas' => 'required|integer|min:1|max:120',
            'data_inicial' => 'required|date',
            'valor' => 'nullable|numeric|min:0',
        ]);

        $venda = Vendas::findOrFail($vendaId);

        // Verificar permissão (empresa_id)
        if ($venda->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'Sem permissão para acessar este contrato.',
            ], 403);
        }

        $quantidadeParcelas = (int) $request->quantidade_parcelas;
        $dataInicial = Carbon::parse($request->data_inicial);
        $valorCustom = $request->valor;

        // Buscar operadora
        $operadora = Operadora::where('nome', $venda->operadora)->first();

        // Resolver valor por parcela
        $valorParcela = $valorCustom;

        if (! $valorParcela && $operadora) {
            $regra = RegrasComissionamento::where('empresa_id', $venda->empresa_id)
                ->where('operadora_id', $operadora->id)
                ->first();

            if ($regra && $regra->vitalicio && $regra->percentual_vitalicio > 0) {
                $valorParcela = ($regra->percentual_vitalicio / 100) * $venda->valor_contrato;
            }
        }

        if (! $valorParcela || $valorParcela <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível determinar o valor das parcelas. Informe o valor manualmente.',
            ], 422);
        }

        // Buscar última parcela existente
        $ultimaParcela = Recebivel::where('venda_id', $venda->id)->max('parcela') ?? 0;

        // Resolver nome do plano
        $planoNome = 'N/A';
        if (! empty($venda->plano_id)) {
            $plano = Plano::find($venda->plano_id);
            $planoNome = $plano?->nome ?? $venda->nome_plano ?? 'N/A';
        } elseif (! empty($venda->nome_plano)) {
            $planoNome = $venda->nome_plano;
        }

        $parcelasCriadas = [];

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $quantidadeParcelas; $i++) {
                $numeroParcela = $ultimaParcela + $i + 1;
                $dataPrevista = $dataInicial->copy()->addMonths($i);

                Recebivel::create([
                    'empresa_id' => $venda->empresa_id,
                    'venda_id' => $venda->id,
                    'vendedor_id' => $venda->user_id,
                    'operadora' => $operadora?->nome ?? $venda->operadora,
                    'plano' => $planoNome,
                    'parcela' => $numeroParcela,
                    'valor' => $valorParcela,
                    'data_prevista' => $dataPrevista,
                    'status' => 'PENDENTE',
                ]);

                $parcelasCriadas[] = [
                    'parcela' => $numeroParcela,
                    'valor' => $valorParcela,
                    'data_prevista' => $dataPrevista->format('d/m/Y'),
                ];
            }

            DB::commit();

            Log::info("[Recebiveis] Geradas {$quantidadeParcelas} parcela(s) manuais para venda {$venda->id}");

            return response()->json([
                'success' => true,
                'message' => "{$quantidadeParcelas} parcela(s) gerada(s) com sucesso.",
                'parcelas_criadas' => $parcelasCriadas,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao gerar recebíveis manuais', [
                'venda_id' => $vendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar parcelas: '.$e->getMessage(),
            ], 500);
        }
    }
}
