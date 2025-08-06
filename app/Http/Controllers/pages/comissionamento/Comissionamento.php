<?php

namespace App\Http\Controllers\pages\comissionamento;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ComissionamentoConfiguracoes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class Comissionamento extends Controller
{
    public function index()
    {
        $users = User::where('empresa_id', auth()->user()->empresa_id)
            ->where('ativo', "Y")
            ->where('user_role_id', UserRole::VENDEDOR)
            ->get();

        return view('content.pages.comissionamento.index', [
            'users' => $users,
        ]);
    }


    public function getCommissioning()
    {
        $configs = ComissionamentoConfiguracoes::with('user')
            ->where('empresa_id', auth()->user()->empresa_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $configs,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'percentual' => 'required|numeric|min:0',
            'periodicidade' => 'required|in:mensal,trimestral,semestral,anual',
            'dia_fechamento' => 'required|integer|min:1|max:31',
        ]);

        $empresaId = auth()->user()->empresa_id;

        $dados = [
            'percentual' => $request->percentual,
            'periodicidade' => $request->periodicidade,
            'pagar_automaticamente' => $request->has('pagar_automaticamente'),
            'dia_fechamento' => $request->dia_fechamento,
        ];

        $comissao = ComissionamentoConfiguracoes::updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'user_id' => $request->user_id
            ],
            $dados
        );

        return response()->json([
            'success' => true,
            'message' => $comissao->wasRecentlyCreated
                ? 'Comissão criada com sucesso.'
                : 'Comissão atualizada com sucesso.'
        ]);
    }


    public function destroy($id)
    {
        $config = ComissionamentoConfiguracoes::where('empresa_id', auth()->user()->empresa_id)
            ->where('id', $id)
            ->firstOrFail();

        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuração de comissão excluída com sucesso.'
        ]);
    }

    public function invoiceCommission()
    {
        return view('content.pages.comissionamento.comissionamento-faturamento', );
    }


    public function getFaturamentoComissionamento(Request $request)
    {
        $periodo = $request->input('periodo');
        [$ano, $mes] = explode('-', $periodo);

        $inicio = Carbon::createFromDate($ano, $mes)->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate($ano, $mes)->endOfMonth()->toDateString();

        $empresaId = auth()->user()->empresa_id;

        $resultados = DB::table('comissionamento_configuracao as cc')
            ->join('users as u', 'u.id', '=', 'cc.user_id')
            ->leftJoin('vendas as v', function ($join) use ($inicio, $fim, $empresaId) {
                $join->on('v.user_id', '=', 'cc.user_id')
                    ->where('v.empresa_id', '=', $empresaId)
                    ->whereNotNull('v.data_implantacao')
                    ->whereBetween('v.data_implantacao', [$inicio, $fim]);
            })
            ->where('cc.empresa_id', $empresaId)
            ->select(
                'cc.user_id',
                'u.name as vendedor',
                DB::raw('COALESCE(SUM(v.valor_contrato), 0) as total_implantado'),
                'cc.percentual',
                DB::raw('COALESCE(ROUND(SUM(v.valor_contrato) * (cc.percentual / 100), 2), 0) as comissao')
            )
            ->groupBy('cc.user_id', 'u.name', 'cc.percentual')
            ->get();

        return response()->json([
            'data' => $resultados,
            'periodo_formatado' => Carbon::createFromDate($ano, $mes)->translatedFormat('m/Y'),
        ]);
    }
}
