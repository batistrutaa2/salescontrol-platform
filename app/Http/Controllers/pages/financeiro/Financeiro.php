<?php

namespace App\Http\Controllers\pages\financeiro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegrasComissionamento;
use App\Models\RegrasComissionamentoParcela;
use App\Models\Operadora;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Recebivel;


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
            'operadora_id'      => 'required|exists:operadoras,id',
            'categoria'         => 'required|in:PME,ADESAO',
            'total_percentual'  => 'nullable|numeric',
            'descricao'         => 'nullable|string|max:255',
            'vitalicio'         => 'boolean',
        ]);

        $rule = RegrasComissionamento::create([
            'empresa_id'       => auth()->user()->empresa_id,
            'operadora_id'     => $data['operadora_id'],
            'categoria'        => $data['categoria'],
            'total_percentual' => $data['total_percentual'] ?? null,
            'descricao'        => $data['descricao'] ?? null,
            'vitalicio'        => $data['vitalicio'] ?? 0,
        ]);

        return response()->json($rule);
    }


    public function regrasUpdate(Request $request, $id)
    {
        $rule = RegrasComissionamento::findOrFail($id);

        $data = $request->validate([
            'operadora_id'     => 'required|exists:operadoras,id',
            'categoria'        => 'required|in:PME,ADESAO',
            'total_percentual' => 'nullable|numeric',
            'descricao'        => 'nullable|string|max:255',
            'vitalicio'        => 'boolean',
        ]);

        $rule->update([
            'operadora_id'     => $data['operadora_id'],
            'categoria'        => $data['categoria'],
            'total_percentual' => $data['total_percentual'] ?? null,
            'descricao'        => $data['descricao'] ?? null,
            'vitalicio'        => $data['vitalicio'] ?? 0,
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
     * 📑 Listagem geral de recebíveis
     */
    public function indexRecebiveis(Request $request)
    {
        $query = Recebivel::with(['venda', 'vendedor', 'empresa'])
            ->orderBy('data_prevista', 'asc');

        // Filtros opcionais
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $recebiveis = $query->get();

        // Calcular totais
        $totais = [
            'pago'     => $recebiveis->where('status', 'PAGO')->sum('valor'),
            'pendente' => $recebiveis->where('status', 'PENDENTE')->sum('valor'),
            'atraso'   => $recebiveis->where('status', 'PENDENTE')
                            ->where('data_prevista', '<', now())->sum('valor'),
        ];

        // Agrupar por contrato
        $contratos = $recebiveis->groupBy('venda_id')->map(function ($parcelas) {
            $valorTotal = $parcelas->sum('valor');
            $valorPago = $parcelas->where('status', 'PAGO')->sum('valor');
            $valorPendente = $valorTotal - $valorPago;

            return (object)[
                'empresa'        => $parcelas->first()->empresa,
                'venda'          => $parcelas->first()->venda,
                'vendedor'       => $parcelas->first()->vendedor,
                'operadora'      => $parcelas->first()->operadora,
                'valor_total'    => $valorTotal,
                'valor_pago'     => $valorPago,
                'valor_pendente' => $valorPendente,
                'em_atraso'      => $parcelas->where('status', 'PENDENTE')
                                            ->where('data_prevista', '<', now())->count() > 0,
            ];
        });

        return view('content.pages.financeiro.recebiveis', [
            'contratos' => $contratos,
            'totais'    => $totais,
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
            ->map(fn($p) => [
                'id'            => $p->id,
                'parcela'       => $p->parcela,
                'valor'         => $p->valor,
                'data_prevista' => \Carbon\Carbon::parse($p->data_prevista)->format('d/m/Y'),
                'status'        => $p->status,
            ]);

        return response()->json($parcelas);
    }

    public function pagarParcela($id)
    {
        $parcela = Recebivel::findOrFail($id);
        $parcela->update(['status' => 'PAGO', 'data_recebimento' => now()]);

        return response()->json(['success' => true]);
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
            'total_aberto'   => $recebiveis->where('status', 'PENDENTE')->sum('valor'),
            'total_cancelado' => $recebiveis->where('status', 'CANCELADO')->sum('valor'),
            'taxa_recebimento' => $recebiveis->sum('valor') > 0
                ? ($recebiveis->where('status', 'PAGO')->sum('valor') / $recebiveis->sum('valor')) * 100
                : 0,
        ];

        // Dados por operadora
        $porOperadora = $recebiveis->groupBy('operadora')->map(function ($items, $operadora) {
            return [
                'operadora' => $operadora ?: 'Não informado',
                'previsto'  => $items->sum('valor'),
                'recebido'  => $items->where('status', 'PAGO')->sum('valor'),
                'aberto'    => $items->where('status', 'PENDENTE')->sum('valor'),
                'cancelado' => $items->where('status', 'CANCELADO')->sum('valor'),
            ];
        })->values();

        // Evolução mensal
        $evolucaoMensal = $recebiveis->groupBy(function($item) {
            return \Carbon\Carbon::parse($item->data_prevista)->format('Y-m');
        })->map(function($items, $mes) {
            return [
                'mes'      => \Carbon\Carbon::parse($mes . '-01')->format('m/Y'),
                'previsto' => $items->sum('valor'),
                'recebido' => $items->where('status', 'PAGO')->sum('valor'),
                'aberto'   => $items->where('status', 'PENDENTE')->sum('valor'),
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
            ->map(function($items) {
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
                'total_aberto'   => (float) $resumo['total_aberto'],
                'total_cancelado' => (float) $resumo['total_cancelado'],
                'taxa_recebimento' => round($resumo['taxa_recebimento'], 2),
                'em_atraso' => (float) $emAtraso,
            ],
            'porOperadora' => $porOperadora->map(fn($op) => [
                'operadora' => $op['operadora'],
                'previsto'  => (float) $op['previsto'],
                'recebido'  => (float) $op['recebido'],
                'aberto'    => (float) $op['aberto'],
                'cancelado' => (float) $op['cancelado'],
            ]),
            'evolucaoMensal' => $evolucaoMensal,
            'statusDistribuicao' => $statusDistribuicao,
            'topVendedores' => $topVendedores,
        ]);
    }

}
