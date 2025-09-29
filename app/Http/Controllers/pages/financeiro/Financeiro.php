<?php

namespace App\Http\Controllers\pages\financeiro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegrasComissionamento;
use App\Models\RegrasComissionamentoParcela;
use App\Models\Operadora;
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
}
