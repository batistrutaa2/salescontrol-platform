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
            $query = RegrasComissionamento::with('operadoras')->select('regras_comissionamento.*');

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
            'operadora_id' => 'required|exists:operadoras,id',
            'modalidade'   => 'required|in:PME,ADESAO',
            'descricao'    => 'nullable|string|max:255',
            'vitalicio_active' => 'boolean',
            'vitalicio_percent' => 'nullable|numeric',
            'vitalicio_starts_at_installment' => 'nullable|integer|min:1',
        ]);

        $rule->update([
            'operadora_id' => $data['operadora_id'],
            'categoria' => $data['modalidade'],
            'descricao' => $data['descricao'] ?? null,
            'vitalicio' => $data['vitalicio_active'] ?? 0,
            'total_percentual' => $data['vitalicio_percent'] ?? null,
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
        $parcela->update(['status' => 'PAGO']);

        return response()->json(['success' => true]);
    }

}
