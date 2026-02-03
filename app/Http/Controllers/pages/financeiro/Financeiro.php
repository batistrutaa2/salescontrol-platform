<?php

namespace App\Http\Controllers\pages\financeiro;

use App\Http\Controllers\Controller;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\Recebivel;
use App\Models\RegrasComissionamento;
use App\Models\RegrasComissionamentoParcela;
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

        // Query base para filtros
        $baseQuery = Recebivel::where('empresa_id', $empresaId);

        if ($anoSelecionado) {
            $baseQuery->whereHas('venda', function ($q) use ($anoSelecionado) {
                $q->whereYear('data_implantacao', $anoSelecionado);
            });
        }

        // Calcular totais via SQL (muito mais rápido)
        $totaisQuery = (clone $baseQuery)->selectRaw("
            SUM(CASE WHEN status = 'PAGO' THEN valor ELSE 0 END) as pago,
            SUM(CASE WHEN status = 'PENDENTE' THEN valor ELSE 0 END) as pendente,
            SUM(CASE WHEN status = 'PENDENTE' AND data_prevista < NOW() THEN valor ELSE 0 END) as atraso,
            SUM(CASE WHEN status = 'PAGO' AND parcela <= 3 THEN valor ELSE 0 END) as parcelas_pago,
            SUM(CASE WHEN status = 'PENDENTE' AND parcela <= 3 THEN valor ELSE 0 END) as parcelas_pendente,
            SUM(CASE WHEN status = 'PAGO' AND parcela >= 4 THEN valor ELSE 0 END) as vitalicio_pago,
            SUM(CASE WHEN status = 'PENDENTE' AND parcela >= 4 THEN valor ELSE 0 END) as vitalicio_pendente
        ")->first();

        $totais = [
            'pago' => (float) ($totaisQuery->pago ?? 0),
            'pendente' => (float) ($totaisQuery->pendente ?? 0),
            'atraso' => (float) ($totaisQuery->atraso ?? 0),
        ];

        $totaisPorTipo = [
            'parcelas' => [
                'pago' => (float) ($totaisQuery->parcelas_pago ?? 0),
                'pendente' => (float) ($totaisQuery->parcelas_pendente ?? 0),
            ],
            'vitalicio' => [
                'pago' => (float) ($totaisQuery->vitalicio_pago ?? 0),
                'pendente' => (float) ($totaisQuery->vitalicio_pendente ?? 0),
            ],
        ];

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
            'totais' => $totais,
            'totaisPorTipo' => $totaisPorTipo,
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
        $parcelas = Recebivel::where('venda_id', $vendaId)
            ->orderBy('parcela')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'parcela' => $p->parcela,
                'valor' => $p->valor,
                'data_prevista' => \Carbon\Carbon::parse($p->data_prevista)->format('d/m/Y'),
                'data_recebimento' => $p->data_recebimento ? \Carbon\Carbon::parse($p->data_recebimento)->format('d/m/Y') : null,
                'status' => $p->status,
                'tipo' => $p->parcela >= 4 ? 'vitalicio' : 'parcela',
            ]);

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
        $operadoras = Operadora::orderBy('nome')->get();

        return view('content.pages.financeiro.relatorio-financeiro', compact('operadoras'));
    }

    public function relatorioFinanceiroFetch(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $query = Recebivel::where('empresa_id', $empresaId);

        if ($request->filled('data_inicial')) {
            $query->whereDate('data_prevista', '>=', $request->data_inicial);
        }
        if ($request->filled('data_final')) {
            $query->whereDate('data_prevista', '<=', $request->data_final);
        }
        if ($request->filled('operadora_id')) {
            $query->where('operadora', Operadora::find($request->operadora_id)?->nome);
        }

        $recebiveis = $query->get();

        // Resumo geral
        $resumo = [
            'total_previsto' => $recebiveis->sum('valor'),
            'total_recebido' => $recebiveis->where('status', 'PAGO')->sum('valor'),
            'total_aberto' => $recebiveis->where('status', 'PENDENTE')->sum('valor'),
            'total_cancelado' => $recebiveis->where('status', 'CANCELADO')->sum('valor'),
            'taxa_recebimento' => $recebiveis->sum('valor') > 0
                ? ($recebiveis->where('status', 'PAGO')->sum('valor') / $recebiveis->sum('valor')) * 100
                : 0,
        ];

        // Dados por operadora
        $porOperadora = $recebiveis->groupBy('operadora')->map(function ($items, $operadora) {
            return [
                'operadora' => $operadora ?: 'Não informado',
                'previsto' => $items->sum('valor'),
                'recebido' => $items->where('status', 'PAGO')->sum('valor'),
                'aberto' => $items->where('status', 'PENDENTE')->sum('valor'),
                'cancelado' => $items->where('status', 'CANCELADO')->sum('valor'),
            ];
        })->values();

        // Evolução mensal
        $evolucaoMensal = $recebiveis->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->data_prevista)->format('Y-m');
        })->map(function ($items, $mes) {
            return [
                'mes' => \Carbon\Carbon::parse($mes.'-01')->format('m/Y'),
                'previsto' => $items->sum('valor'),
                'recebido' => $items->where('status', 'PAGO')->sum('valor'),
                'aberto' => $items->where('status', 'PENDENTE')->sum('valor'),
            ];
        })->sortKeys()->values();

        // Status distribution
        $statusDistribuicao = [
            ['status' => 'Recebido', 'valor' => $recebiveis->where('status', 'PAGO')->sum('valor')],
            ['status' => 'Pendente', 'valor' => $recebiveis->where('status', 'PENDENTE')->sum('valor')],
            ['status' => 'Cancelado', 'valor' => $recebiveis->where('status', 'CANCELADO')->sum('valor')],
        ];

        // Recebíveis em atraso
        $emAtraso = $recebiveis->where('status', 'PENDENTE')
            ->where('data_prevista', '<', now())
            ->sum('valor');

        // Top vendedores
        $topVendedores = $recebiveis->where('status', 'PAGO')
            ->groupBy('vendedor_id')
            ->map(function ($items) {
                $vendedor = $items->first()->vendedor;

                return [
                    'vendedor' => $vendedor ? $vendedor->name : 'Não informado',
                    'valor' => $items->sum('valor'),
                    'quantidade' => $items->count(),
                ];
            })
            ->sortByDesc('valor')
            ->take(10)
            ->values();

        return response()->json([
            'resumo' => [
                'total_previsto' => (float) $resumo['total_previsto'],
                'total_recebido' => (float) $resumo['total_recebido'],
                'total_aberto' => (float) $resumo['total_aberto'],
                'total_cancelado' => (float) $resumo['total_cancelado'],
                'taxa_recebimento' => round($resumo['taxa_recebimento'], 2),
                'em_atraso' => (float) $emAtraso,
            ],
            'porOperadora' => $porOperadora->map(fn ($op) => [
                'operadora' => $op['operadora'],
                'previsto' => (float) $op['previsto'],
                'recebido' => (float) $op['recebido'],
                'aberto' => (float) $op['aberto'],
                'cancelado' => (float) $op['cancelado'],
            ]),
            'evolucaoMensal' => $evolucaoMensal,
            'statusDistribuicao' => $statusDistribuicao,
            'topVendedores' => $topVendedores,
        ]);
    }

    /**
     * Retorna os KPIs dos recebíveis (para atualização via AJAX) - Otimizado
     */
    public function getKpis(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;
        $anoSelecionado = $request->ano;

        // Query base
        $query = Recebivel::where('empresa_id', $empresaId);

        if ($anoSelecionado) {
            $query->whereHas('venda', function ($q) use ($anoSelecionado) {
                $q->whereYear('data_implantacao', $anoSelecionado);
            });
        }

        // Calcular tudo em uma única query SQL
        $totaisQuery = $query->selectRaw("
            SUM(CASE WHEN status = 'PAGO' THEN valor ELSE 0 END) as pago,
            SUM(CASE WHEN status = 'PENDENTE' THEN valor ELSE 0 END) as pendente,
            SUM(CASE WHEN status = 'PENDENTE' AND data_prevista < NOW() THEN valor ELSE 0 END) as atraso,
            SUM(CASE WHEN status = 'PAGO' AND parcela <= 3 THEN valor ELSE 0 END) as parcelas_pago,
            SUM(CASE WHEN status = 'PENDENTE' AND parcela <= 3 THEN valor ELSE 0 END) as parcelas_pendente,
            SUM(CASE WHEN status = 'PAGO' AND parcela >= 4 THEN valor ELSE 0 END) as vitalicio_pago,
            SUM(CASE WHEN status = 'PENDENTE' AND parcela >= 4 THEN valor ELSE 0 END) as vitalicio_pendente
        ")->first();

        return response()->json([
            'success' => true,
            'totais' => [
                'pago' => (float) ($totaisQuery->pago ?? 0),
                'pendente' => (float) ($totaisQuery->pendente ?? 0),
                'atraso' => (float) ($totaisQuery->atraso ?? 0),
            ],
            'totais_por_tipo' => [
                'parcelas' => [
                    'pago' => (float) ($totaisQuery->parcelas_pago ?? 0),
                    'pendente' => (float) ($totaisQuery->parcelas_pendente ?? 0),
                ],
                'vitalicio' => [
                    'pago' => (float) ($totaisQuery->vitalicio_pago ?? 0),
                    'pendente' => (float) ($totaisQuery->vitalicio_pendente ?? 0),
                ],
            ],
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
}
