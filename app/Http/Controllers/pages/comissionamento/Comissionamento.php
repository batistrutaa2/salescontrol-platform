<?php

namespace App\Http\Controllers\pages\comissionamento;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ComissionamentoConfiguracoes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class Comissionamento extends Controller
{
    public function index()
    {
        $users = User::where('empresa_id', auth()->user()->empresa_id)
            ->where('ativo', "Y")
            ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::ADMINISTRATIVO])
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
        ]);

        $empresaId = auth()->user()->empresa_id;

        $dados = [
            'percentual' => $request->percentual,
            'imposto' => $request->imposto,
            'grade' => $request->grade,
            'salario' => $request->salario,
            'periodicidade' => $request->periodicidade
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
        $empresaId = Auth::user()->empresa_id;

        $mes = $request->input('mes');
        if (!$mes) {
            $mes = Carbon::now('America/Sao_Paulo')->format('Y-m');
        }

        [$y, $m] = explode('-', $mes);
        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();

        $vendedorId = $request->input('vendedor_id');

        $lastCc = DB::table('contatos_corretores as cc')
            ->join(DB::raw('(SELECT MAX(id) AS id FROM contatos_corretores WHERE empresa_id = ' . (int) $empresaId . ' GROUP BY contato_id) AS last_cc'), 'last_cc.id', '=', 'cc.id')
            ->where('cc.empresa_id', $empresaId)
            ->select('cc.contato_id', 'cc.tabulacao_id');

        // Query principal
        $rows = DB::table('vendas as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            // só vendedores configurados para comissionamento na empresa
            ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
                $j->on('cfg.user_id', '=', 'v.user_id')
                    ->where('cfg.empresa_id', '=', $empresaId);
            })
            // status IMPLANTADO via tabulacoes (usando a última tabulação do contato)
            ->joinSub($lastCc, 'lc', function ($j) {
                $j->on('lc.contato_id', '=', 'v.contato_id');
            })
            ->join('tabulacoes as t', function ($j) use ($empresaId) {
                $j->on('t.id', '=', 'lc.tabulacao_id')
                    ->where('t.empresa_id', '=', $empresaId);
            })
            ->where('v.empresa_id', $empresaId)
            ->whereBetween('v.data_implantacao', [$inicio, $fim])
            ->where('v.comissao_paga', 0)
            ->whereRaw('UPPER(t.descricao) = "IMPLANTADO"')
            ->when($vendedorId, fn($q) => $q->where('v.user_id', $vendedorId))
            ->select([
                'v.id',
                'v.user_id',
                'u.name as vendedor',
                'v.nome_contrato',
                'v.valor_contrato',
                'v.data_implantacao',
                'cfg.percentual',
                DB::raw('(v.valor_contrato * (cfg.percentual / 100.0)) as valor_comissao'),
            ])
            ->orderBy('u.name')
            ->orderBy('v.data_implantacao')
            ->get();

        // Agrupa por vendedor e calcula KPIs
        $porVendedor = [];
        $kpiContratos = 0;
        $kpiTotalContratos = 0.0;
        $kpiTotalComissao = 0.0;

        foreach ($rows as $r) {
            $porVendedor[$r->user_id] ??= [
                'user_id' => $r->user_id,
                'vendedor' => $r->vendedor,
                'percentual' => (float) $r->percentual,
                'totais' => ['qtd' => 0, 'contratos' => 0.0, 'comissao' => 0.0],
                'contratos' => [],
            ];

            $porVendedor[$r->user_id]['contratos'][] = [
                'id' => $r->id,
                'nome_contrato' => $r->nome_contrato,
                'valor_contrato' => (float) $r->valor_contrato,
                'valor_comissao' => (float) $r->valor_comissao,
                'data_implantacao' => $r->data_implantacao,
            ];

            $porVendedor[$r->user_id]['totais']['qtd'] += 1;
            $porVendedor[$r->user_id]['totais']['contratos'] += (float) $r->valor_contrato;
            $porVendedor[$r->user_id]['totais']['comissao'] += (float) $r->valor_comissao;

            $kpiContratos += 1;
            $kpiTotalContratos += (float) $r->valor_contrato;
            $kpiTotalComissao += (float) $r->valor_comissao;
        }

        $payload = [
            'empresa_id' => $empresaId,
            'mes' => $mes,
            'filtro' => [
                'inicio' => $inicio,
                'fim' => $fim,
                'vendedor_id' => $vendedorId ? (int) $vendedorId : null,
            ],
            'kpis' => [
                'vendedores' => count($porVendedor),
                'contratos' => $kpiContratos,
                'total_contratos' => round($kpiTotalContratos, 2),
                'total_comissao' => round($kpiTotalComissao, 2),
            ],
            'vendedores' => array_values($porVendedor),
        ];

        return response()->json($payload);
    }

}
