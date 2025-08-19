<?php

namespace App\Http\Controllers\pages\ranking;

use App\Http\Controllers\Controller;
use App\Models\MetaConfiguracoes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Enums\Tabulations;

class RankingVendas extends Controller
{
    public function index()
    {
        $metas = MetaConfiguracoes::where('empresa_id', auth()->user()->empresa_id)->get();
        return view('content.pages.ranking.index', ['metas' => $metas]);
    }

    public function edit($id)
    {
        $meta = MetaConfiguracoes::findOrFail($id);
        return view('content.pages.ranking.edit', ['meta' => $meta]);
    }

    public function config()
    {
        return view('content.pages.ranking.config');
    }


    public function rankingVendas()
    {
        return view('content.pages.ranking.rankingVendas');
    }


    public function rankingVendasData()
    {
        $empresaId = auth()->user()->empresa_id;
        $meta = MetaConfiguracoes::where('ativa', true)
            ->first();

        if (!$meta) {
            return response()->json(['message' => 'Nenhuma meta ativa encontrada.'], 404);
        }

        $query = DB::table('vendas')
            ->join('users', 'users.id', '=', 'vendas.user_id')
            ->join('contatos_corretores', 'contatos_corretores.contato_id', '=', 'vendas.contato_id')
            ->where('vendas.empresa_id', $empresaId)
            ->whereIn('contatos_corretores.tabulacao_id', [
                Tabulations::VENDA,
                Tabulations::IMPLANTADO,
                Tabulations::PENDENCIA,
                Tabulations::ANALISE_OPERADORA,
                Tabulations::BOLETO_DISPONIVEL,
                Tabulations::REGULARIZADO,
                Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                Tabulations::ANALISE_DOCUMENTOS,
                Tabulations::AGUARD_ASSINATURA_DS
            ]);


        if ($meta->periodo === 'MENSAL') {
            $query->whereMonth('vendas.created_at', now()->month)
                ->whereYear('vendas.created_at', now()->year);
        } elseif ($meta->periodo === 'SEMANAL') {
            $query->whereBetween('vendas.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($meta->periodo === 'PERSONALIZADO') {
            $query->whereBetween('vendas.created_at', [$meta->data_inicio, $meta->data_fim]);
        }

        // 📊 Cálculo baseado no tipo da meta
        if ($meta->tipo_calculo === 'VALOR') {
            $vendedores = $query
                ->select('users.id', 'users.name as nome', DB::raw('SUM(vendas.valor_contrato) as total'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total')
                ->get();
        } else {
            $vendedores = $query
                ->select('users.id', 'users.name as nome', DB::raw('COUNT(vendas.id) as total'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total')
                ->get();
        }

        return response()->json([
            'meta' => [
                'descricao' => $meta->nome,
                'valor' => $meta->valor_meta,
                'tipo_calculo' => $meta->tipo_calculo,
                'valor_meta' => $meta->valor_meta,
                'quantidade_meta' => $meta->quantidade_meta
            ],
            'vendedores' => $vendedores
        ]);


    }

    public function valoresMensais(Request $request)
    {
        $tz = 'America/Sao_Paulo';

        $year = (int) ($request->input('year') ?? now($tz)->year);
        $month = (int) ($request->input('month') ?? now($tz)->month);

        $start = Carbon::createFromDate($year, $month, 1, $tz)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $rows = DB::table('vendas as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->join('contatos_corretores', 'contatos_corretores.contato_id', '=', 'v.contato_id')
            ->selectRaw('v.user_id, u.name as vendedor, COALESCE(SUM(v.valor_contrato),0) as total')
            ->where('v.empresa_id', auth()->user()->empresa_id)
            ->when($request->integer('empresa_id'), fn($q, $v) => $q->where('v.empresa_id', $v))
            ->whereBetween('v.created_at', [$start, $end])
            ->whereIn('contatos_corretores.tabulacao_id', [
                Tabulations::VENDA,
                Tabulations::IMPLANTADO,
                Tabulations::PENDENCIA,
                Tabulations::ANALISE_OPERADORA,
                Tabulations::BOLETO_DISPONIVEL,
                Tabulations::REGULARIZADO,
                Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
                Tabulations::ANALISE_DOCUMENTOS,
                Tabulations::AGUARD_ASSINATURA_DS
            ])
            ->groupBy('v.user_id', 'u.name')
            ->orderByDesc('total')
            ->get();

        return response()->json(['data' => $rows], 200);
    }


}
