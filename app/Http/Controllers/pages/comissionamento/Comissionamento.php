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
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\LancamentoDebitoCredito;
use App\Models\ContaPagamento;
use App\Models\ComissaoPagamento;



class Comissionamento extends Controller
{
    public function index()
    {
        $users = User::where('empresa_id', auth()->user()->empresa_id)
            ->where('ativo', "Y")
            ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])
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

    public function getVendedores(Request $request)
    {
        $empresaId = $request->input('empresa_id') ?? Auth::user()->empresa_id;

        $vendedores = User::where('empresa_id', $empresaId)
            ->where('ativo', 'Y')
            ->where('user_role_id', UserRole::VENDEDOR)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'vendedores' => $vendedores
        ]);
    }
    
public function getFaturamentoComissionamento(Request $request)
{
    $empresaId = Auth::user()->empresa_id;

    $mes = $request->input('mes') ?: Carbon::now('America/Sao_Paulo')->format('Y-m');
    [$y, $m] = explode('-', $mes);

    $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
    $fim    = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();
    $vendedorId = $request->input('vendedor_id');

    // ========== BASE DE VENDAS ==========
    $rows = DB::table('vendas as v')
        ->join('users as u', 'u.id', '=', 'v.user_id')
        ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
            $j->on('cfg.user_id', '=', 'v.user_id')
              ->where('cfg.empresa_id', '=', $empresaId);
        })
        ->where('v.empresa_id', $empresaId)
        ->whereBetween('v.data_implantacao', [$inicio, $fim])
        ->whereNotNull('v.data_implantacao')
        ->where('v.comissao_paga', 0)
        ->when($vendedorId, fn($q) => $q->where('v.user_id', $vendedorId))
        ->select([
            'v.id',
            'v.user_id',
            'u.name as vendedor',
            'v.nome_contrato',
            'v.angariacao_valor',
            'v.angariacao_status',
            DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
            'v.data_implantacao',
            DB::raw('LOWER(cfg.grade) as grade'),
            'cfg.imposto',
            'cfg.salario',
            'cfg.percentual',
        ])
        ->orderBy('u.name')
        ->orderBy('v.data_implantacao')
        ->get();

    // ========== CÁLCULOS ==========
    $porVendedor = [];
    $kpiContratos = 0;
    $kpiTotalContratos = 0.0;
    $kpiTotalComissao = 0.0;

    $totalVendasAllGrades = 0.0; // base ADMIN
    $totalVendasJunior    = 0.0; // base COMERCIAL

    foreach ($rows as $r) {
        $valor      = (float) $r->valor_contrato;
        $impostoCfg = (float) $r->imposto;
        $grade      = strtolower((string) $r->grade);
        $angVal     = (float) $r->angariacao_valor;
        $percentualAngariacao = $r->grade == 'junior' ? 30.0 : 50.0;

        // Regra angariação
        $isAng           = strtoupper((string) $r->angariacao_status) === 'SIM';
        $baseAplicada    = $isAng ? $angVal : $valor;
        $percentualAplic = $isAng ? $percentualAngariacao : (float) $r->percentual;
        $impostoAplic    = $isAng ? 0.0  : $impostoCfg;

        if ($percentualAplic > 0) {
            $valorComissaoBruta  = round($baseAplicada * ($percentualAplic / 100.0), 2);
            $valorComissaoLiquida= round($valorComissaoBruta * (1.0 - ($impostoAplic / 100.0)), 2);

            $totalVendasAllGrades += $baseAplicada;
            if ($grade === 'junior') $totalVendasJunior += $baseAplicada;

            if (!isset($porVendedor[$r->user_id])) {
                $porVendedor[$r->user_id] = [
                    'user_id'   => $r->user_id,
                    'vendedor'  => $r->vendedor,
                    'percentual'=> (float) $r->percentual,
                    'totais'    => ['qtd' => 0, 'contratos' => 0.0, 'comissao' => 0.0],
                    'contratos' => [],
                ];
            }

            $porVendedor[$r->user_id]['contratos'][] = [
                'id'                     => $r->id,
                'is_ajuste'              => false, // CONTRATO
                'nome_contrato'          => $r->nome_contrato,
                'valor_contrato'         => round($valor, 2),
                'valor_base'             => round($baseAplicada, 2),
                'percentual_aplicado'    => round($percentualAplic, 2),
                'imposto_aplicado'       => round($impostoAplic, 2),
                'valor_comissao_bruta'   => round($valorComissaoBruta, 2),
                'valor_comissao'         => round($valorComissaoLiquida, 2),
                'data_implantacao'       => Carbon::parse($r->data_implantacao)->format('d/m/Y'),
                'angariacao_valor'       => round($angVal, 2),
                'angariacao_status'      => $r->angariacao_status,
            ];

            $porVendedor[$r->user_id]['totais']['qtd']         += 1;
            $porVendedor[$r->user_id]['totais']['contratos']   += $baseAplicada;
            $porVendedor[$r->user_id]['totais']['comissao']    += $valorComissaoLiquida;

            $kpiContratos       += 1;
            $kpiTotalContratos  += $baseAplicada;
            $kpiTotalComissao   += $valorComissaoLiquida;
        }
    }

    /* ===== AJUSTES (pendentes) para o mês/empresa, e opcionalmente vendedor ===== */
    $ajustes = DB::table('lancamentos_debito_credito as a')
        ->join('users as u', 'u.id', '=', 'a.vendedor_id')
        ->where('a.empresa_id', $empresaId)
        ->where('a.mes', $mes)
        ->where('a.status', 'pendente')
        ->when($vendedorId, fn($q) => $q->where('a.vendedor_id', $vendedorId))
        ->select([
            'a.id',
            'a.vendedor_id as user_id',
            'u.name as vendedor',
            'a.natureza',
            'a.categoria',
            'a.descricao',
            'a.imposto_perc',
            'a.valor_bruto',
            'a.imposto_valor',
            'a.valor_liquido', // já com sinal (+ crédito / - débito)
        ])
        ->orderBy('u.name')
        ->get();

    foreach ($ajustes as $aj) {
        if (!isset($porVendedor[$aj->user_id])) {
            $porVendedor[$aj->user_id] = [
                'user_id'   => $aj->user_id,
                'vendedor'  => $aj->vendedor,
                'percentual'=> 0.0,
                'totais'    => ['qtd' => 0, 'contratos' => 0.0, 'comissao' => 0.0],
                'contratos' => [],
            ];
        }

        $nomeSintetico = ($aj->natureza === 'CREDITO' ? 'Crédito' : 'Despesa')
            .' · '.ucfirst(strtolower($aj->categoria))
            .($aj->descricao ? ' — '.$aj->descricao : '');

        $porVendedor[$aj->user_id]['contratos'][] = [
            'id'                   => $aj->id,
            'is_ajuste'            => true,                 // FLAG para o front
            'nome_contrato'        => $nomeSintetico,
            'valor_contrato'       => round((float)$aj->valor_bruto, 2),
            'valor_base'           => round((float)$aj->valor_bruto, 2),
            'percentual_aplicado'  => 0,
            'imposto_aplicado'     => round((float)$aj->imposto_perc, 2),
            'valor_comissao_bruta' => round((float)$aj->valor_bruto, 2),
            'valor_comissao'       => round((float)$aj->valor_liquido, 2), // LÍQUIDO COM SINAL
            'data_implantacao'     => '',    // não se aplica
            'angariacao_valor'     => 0,
            'angariacao_status'    => 'NAO',
        ];

        // Ajuste entra SOMENTE na soma da comissão líquida (com sinal)
        $porVendedor[$aj->user_id]['totais']['comissao'] += (float)$aj->valor_liquido;
        $kpiTotalComissao += (float)$aj->valor_liquido;
    }

    // (Opcional) ordenar vendedores por nome depois de injetar ajustes
    if (!empty($porVendedor)) {
        $porVendedor = array_values(
            collect($porVendedor)->sortBy('vendedor', SORT_NATURAL|SORT_FLAG_CASE)->toArray()
        );
    } else {
        $porVendedor = [];
    }

    // ========== ADMIN ==========
    $admins = DB::table('comissionamento_configuracao as cfg')
        ->join('users as u', 'u.id', '=', 'cfg.user_id')
        ->where('cfg.empresa_id', $empresaId)
        ->whereRaw('LOWER(cfg.grade) = "admin"')
        ->select('cfg.user_id', 'u.name as nome', 'cfg.imposto', 'cfg.percentual')
        ->get();

    $adminUsuarios = [];
    foreach ($admins as $ad) {
        $bruta  = $totalVendasAllGrades * ((float) $ad->percentual / 100.0);
        $liquida= $bruta * (1.0 - ((float) $ad->imposto / 100.0));
        $adminUsuarios[] = [
            'user_id'          => $ad->user_id,
            'nome'             => $ad->nome,
            'percentual_base'  => (float) $ad->percentual,
            'comissao_bruta'   => round($bruta, 2),
            'comissao_liquida' => round($liquida, 2),
            'imposto'          => (float) $ad->imposto,
        ];
    }

    // ========== COMERCIAL (supervisores) ==========
    $salariosJuniorTot = (float) DB::table('comissionamento_configuracao')
        ->where('empresa_id', $empresaId)
        ->whereRaw('LOWER(grade) = "junior"')
        ->sum('salario');

    $custoAdm5 = $totalVendasJunior * 0.05;

    $poolFinal = $totalVendasJunior - $salariosJuniorTot - $custoAdm5;
    $desconto  = $poolFinal * 0.10;
    $poolFinal = $poolFinal - $desconto;

    $gestores = DB::table('comissionamento_configuracao as cfg')
        ->join('users as u', 'u.id', '=', 'cfg.user_id')
        ->where('cfg.empresa_id', $empresaId)
        ->whereRaw('LOWER(cfg.grade) = "comercial"')
        ->select('cfg.user_id', 'u.name as nome')
        ->get();

    $qtdGestores = $gestores->count();
    $quota = $qtdGestores > 0 ? ($poolFinal / $qtdGestores) : 0.0;

    $gestoresArr = [];
    foreach ($gestores as $g) {
        $gestoresArr[] = [
            'user_id' => $g->user_id,
            'nome'    => $g->nome,
            'quota'   => round($quota, 2),
        ];
    }

    // ========== PAYLOAD ==========
    $payload = [
        'empresa_id' => $empresaId,
        'mes'        => $mes,
        'filtro'     => [
            'inicio'      => $inicio,
            'fim'         => $fim,
            'vendedor_id' => $vendedorId ? (int) $vendedorId : null,
        ],
        'kpis'       => [
            'vendedores'       => count($porVendedor),
            'contratos'        => $kpiContratos,
            'total_contratos'  => round($kpiTotalContratos, 2),
            'total_comissao'   => round($kpiTotalComissao, 2),
        ],
        'vendedores' => $porVendedor, // já ordenado

        'grades' => [
            'bases' => [
                'total_vendas_all_grades' => round($totalVendasAllGrades, 2),
                'total_vendas_junior'     => round($totalVendasJunior, 2),
            ],
            'admin' => [
                'usuarios'      => $adminUsuarios,
                'total_liquido' => round(array_sum(array_column($adminUsuarios, 'comissao_liquida')), 2),
            ],
            'comercial' => [
                'qtd_gestores'        => $qtdGestores,
                'base_junior'         => round($totalVendasJunior, 2),
                'salarios_junior_tot' => round($salariosJuniorTot, 2),
                'custo_admin_5'       => round($custoAdm5, 2),
                'pool_final'          => round($poolFinal, 2),
                'quota'               => round($quota, 2),
                'gestores'            => $gestoresArr,
                'total_distribuido'   => round($quota * $qtdGestores, 2),
            ],
        ],
    ];

    return response()->json($payload);
}



    public function sellerCommission()
    {
        return view('content.pages.comissionamento.comissionamento-vendedor', );
    }

    public function getCommissioningBySeller(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $userId = Auth::id();

        $mes = $request->input('mes') ?: Carbon::now('America/Sao_Paulo')->format('Y-m');
        [$y, $m] = explode('-', $mes);

        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();

        $rows = DB::table('vendas as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
                $j->on('cfg.user_id', '=', 'v.user_id')
                    ->where('cfg.empresa_id', '=', $empresaId);
            })
            ->where('v.empresa_id', operator: $empresaId)
            ->whereBetween('v.data_implantacao', [$inicio, $fim])
            ->whereNotNull('v.data_implantacao')
            ->where('v.comissao_paga', 0)
            ->where('v.user_id', $userId)
            ->select([
                'v.id',
                'v.user_id',
                'u.name as vendedor',
                'v.nome_contrato',
                'v.angariacao_valor',
                'v.angariacao_status',
                DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
                'v.data_implantacao',
                DB::raw('LOWER(cfg.grade) as grade'),
                'cfg.imposto',
                'cfg.salario',
                'cfg.percentual',
            ])
            ->orderBy('u.name')
            ->orderBy('v.data_implantacao')
            ->get();

        return response()->json([
            'data' => $rows
        ]);
    }

    public function getCommissioningBySellerPdf(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;
        $userId = Auth::id();

        $mes = $request->input('mes') ?: Carbon::now('America/Sao_Paulo')->format('Y-m');
        [$y, $m] = explode('-', $mes);

        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();

        $rows = DB::table('vendas as v')
            ->join('users as u', 'u.id', '=', 'v.user_id')
            ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
                $j->on('cfg.user_id', '=', 'v.user_id')
                    ->where('cfg.empresa_id', '=', $empresaId);
            })
            ->where('v.empresa_id', $empresaId)
            ->whereBetween('v.data_implantacao', [$inicio, $fim])
            ->whereNotNull('v.data_implantacao')
            ->where('v.comissao_paga', 0)
            ->where('v.user_id', $userId)
            ->select([
                'v.id',
                'v.user_id',
                'u.name as vendedor',
                'v.nome_contrato',
                'v.angariacao_valor',
                'v.angariacao_status',
                DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
                'v.data_implantacao',
                DB::raw('LOWER(cfg.grade) as grade'),
                'cfg.imposto',
                'cfg.salario',
                'cfg.percentual',
            ])
            ->orderBy('u.name')
            ->orderBy('v.data_implantacao')
            ->get();

        // cálculos por linha
        $enriched = $rows->map(function ($r) {
            $valorContrato = (float) $r->valor_contrato;
            $perc = isset($r->percentual) ? (float) $r->percentual : 0.0;
            $impostoPerc = isset($r->imposto) ? (float) $r->imposto : 10.0;

            $bruto = $valorContrato * ($perc / 100.0);
            $impVal = $bruto * ($impostoPerc / 100.0);
            $liquido = $bruto - $impVal;

            $r->bruto = $bruto;
            $r->imposto_valor = $impVal;
            $r->liquido = $liquido;
            return $r;
        });

        // totais
        $totais = [
            'bruto' => $enriched->sum('bruto'),
            'imposto' => $enriched->sum('imposto_valor'),
            'liquido' => $enriched->sum('liquido'),
        ];

        // perfil (1º registro; se quiser modo "mais frequente", posso ajustar)
        $perfil = [
            'grade' => optional($rows->first())->grade,
            'percentual' => optional($rows->first())->percentual,
            'salario' => optional($rows->first())->salario,
            'imposto' => optional($rows->first())->imposto ?? 10,
        ];

        // total a receber = salário (cfg) + comissão líquida total
        $totalReceber = ((float) ($perfil['salario'] ?? 0)) + ((float) ($totais['liquido'] ?? 0));

        $vendedor = optional($rows->first())->vendedor ?? '—';
        $periodo = Carbon::createFromDate((int) $y, (int) $m, 1)->locale('pt_BR')->isoFormat('MMMM [de] YYYY');

        $pdf = Pdf::loadView('pdf.comissionamento-vendedor', [
            'mes' => $mes,
            'periodo' => mb_convert_case($periodo, MB_CASE_TITLE, 'UTF-8'),
            'vendedor' => $vendedor,
            'linhas' => $enriched,
            'totais' => $totais,
            'perfil' => $perfil,
            'totalReceber' => $totalReceber, // <-- novo
        ])->setPaper('a4', 'landscape');       // <-- orientação horizontal

        return $pdf->stream("comissionamento_{$mes}.pdf");
        // Para download direto: return $pdf->download("comissionamento_{$mes}.pdf");
    }

    public function storeLancamentoDebitoCredito(Request $request)
        {
            $user = Auth::user();

            // Validação + normalizações simples
            $data = $request->validate([
                'vendedor_id'  => ['required','integer','exists:users,id'],
                'mes'          => ['required','regex:/^\d{4}-\d{2}$/'], // YYYY-MM
                'natureza'     => ['required','in:DEBITO,CREDITO'],
                'categoria'    => ['required','in:MOTIVACIONAL,AJUSTE,DESCONTO,OUTRO,ANGARIACAO'],
                'imposto_perc' => ['required','numeric','min:0','max:100'],
                'valor_bruto'  => ['required','numeric','min:0.01'],
                'descricao'    => ['nullable','string','max:255'],
                // Os campos abaixo vêm do front, mas serão ignorados p/ segurança:
                'imposto_valor'=> ['nullable'],
                'valor_liquido'=> ['nullable'],
            ]);

            // (opcional) garantir que vendedor pertence à mesma empresa do usuário logado
            // abort_if(!User::where('id',$data['vendedor_id'])->where('empresa_id',$user->empresa_id)->exists(), 403);

            // Cria: o Model recalcula imposto_valor e valor_liquido no saving()
            $lanc = LancamentoDebitoCredito::create([
                'empresa_id'   => $user->empresa_id,
                'vendedor_id'  => (int)$data['vendedor_id'],
                'created_by'   => $user->id,
                'natureza'     => $data['natureza'],
                'categoria'    => $data['categoria'],
                'valor_bruto'  => (float)$data['valor_bruto'],
                'imposto_perc' => (float)$data['imposto_perc'],
                'mes'          => $data['mes'],                  // YYYY-MM
                'descricao'    => $data['descricao'] ?? null,
                'status'       => LancamentoDebitoCredito::ST_PENDENTE,
            ]);

            return response()->json([
                'message' => 'Ajuste lançado com sucesso.',
                'lancamento' => $lanc,
            ], 201);
        }
    


    public function pagarVendedor(Request $request)
    {
        $request->validate([
            'mes'             => ['required', 'regex:/^\d{4}\-\d{2}$/'],
            'vendedor_id'     => ['required', 'integer', 'exists:users,id'],
            'data_pagamento'  => ['required', 'date'],
            'venda_ids'       => ['array'],
            'venda_ids.*'     => ['integer'],
            'ajuste_ids'      => ['array'],
            'ajuste_ids.*'    => ['integer'],
        ]);

        $empresaId   = Auth::user()->empresa_id;
        $adminUserId = Auth::id();
        $vendedorId  = (int) $request->integer('vendedor_id');
        $mes         = $request->input('mes');
        $dataPagto   = Carbon::parse($request->input('data_pagamento'))->toDateString();

        [$y, $m] = explode('-', $mes);
        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim    = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();

        $vendaIds  = array_map('intval', $request->input('venda_ids', []));
        $ajusteIds = array_map('intval', $request->input('ajuste_ids', []));

        /* ================== VENDAS ================== */
        $rows = collect();
        if (!empty($vendaIds)) {
            $rows = DB::table('vendas as v')
                ->join('users as u', 'u.id', '=', 'v.user_id')
                ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
                    $j->on('cfg.user_id', '=', 'v.user_id')->where('cfg.empresa_id', '=', $empresaId);
                })
                ->where('v.empresa_id', $empresaId)
                ->where('v.user_id', $vendedorId)
                ->whereBetween('v.data_implantacao', [$inicio, $fim])
                ->whereNotNull('v.data_implantacao')
                ->where('v.comissao_paga', 0)
                ->whereIn('v.id', $vendaIds)
                ->select([
                    'v.id',
                    'v.user_id',
                    'u.name as vendedor',
                    DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
                    'v.data_implantacao',
                    DB::raw('LOWER(cfg.grade) as grade'),
                    'cfg.imposto',
                    'cfg.percentual',
                    'v.angariacao_status',
                    DB::raw('COALESCE(v.angariacao_valor,0) as angariacao_valor'),
                ])
                ->orderBy('v.data_implantacao')
                ->get();
        }

        $enriched = $rows->map(function ($r) {
            $isAng = strtoupper((string) $r->angariacao_status) === 'SIM';
            $percentualAngariacao = $r->grade == 'junior' ? 30.0 : 50.0;

            // Base e % de comissão conforme regra
            $base = $isAng ? (float) $r->angariacao_valor : (float) $r->valor_contrato;
            $perc = $isAng ? $percentualAngariacao : (isset($r->percentual) ? (float) $r->percentual : 0.0);

            // imposto: se angariação, é 0%
            $impP = $isAng ? 0.0 : (isset($r->imposto) ? (float) $r->imposto : 10.0);

            $bruto   = round($base * ($perc / 100), 2);
            $impVal  = $isAng ? 0.0 : round($bruto * ($impP / 100), 2);
            $liquido = round($bruto - $impVal, 2);

            $r->percentual           = $perc;
            $r->imposto_perc         = $impP;
            $r->bruto                = $bruto;
            $r->imposto_valor        = $impVal;
            $r->liquido              = $liquido;
            $r->valor_base_para_item = $base;

            return $r;
        });

        /* ================== AJUSTES ================== */
        $ajustes = collect();
        if (!empty($ajusteIds)) {
            $ajustes = DB::table('lancamentos_debito_credito as a')
                ->where('a.empresa_id', $empresaId)
                ->where('a.vendedor_id', $vendedorId)
                ->where('a.mes', $mes)
                ->where('a.status', 'pendente')
                ->whereIn('a.id', $ajusteIds)
                ->select([
                    'a.id',
                    'a.natureza',        // DEBITO | CREDITO
                    'a.categoria',       // MOTIVACIONAL | AJUSTE | BONUS | OUTRO
                    'a.descricao',
                    'a.imposto_perc',
                    'a.valor_bruto',
                    'a.imposto_valor',
                    'a.valor_liquido',   // já vem com sinal (débito negativo)
                ])->get();
        }

        if ($enriched->isEmpty() && $ajustes->isEmpty()) {
            return response()->json(['message' => 'Nenhum item selecionado para pagamento.'], 422);
        }

        /* ================== TOTAIS ================== */
        $totVendas = [
            'bruto'   => (float) $enriched->sum('bruto'),
            'imposto' => (float) $enriched->sum('imposto_valor'),
            'liquido' => (float) $enriched->sum('liquido'),
        ];

        $totAjustes = [
            'bruto'   => (float) $ajustes->sum('valor_bruto'),
            'imposto' => (float) $ajustes->sum('imposto_valor'),
            'liquido' => (float) $ajustes->sum('valor_liquido'), // já com sinal
        ];

        $totais = [
            'bruto'   => round($totVendas['bruto']   + $totAjustes['bruto'], 2),
            'imposto' => round($totVendas['imposto'] + $totAjustes['imposto'], 2),
            'liquido' => round($totVendas['liquido'] + $totAjustes['liquido'], 2),
        ];

        // Snapshot de perfil (para header): se não houver vendas, usa 0
        $cfg      = $rows->first();
        $salario  = 0.0; // <<< SALÁRIO FORA DO CÁLCULO
        $percCom  = (float) ($cfg->percentual ?? 0);
        $percImp  = (float) ($cfg->imposto ?? 10);
        $totalRec = round($totais['liquido'], 2); // <<< TOTAL A RECEBER = APENAS LÍQUIDO

        $vendedor = $rows->first()->vendedor ?? DB::table('users')->where('id', $vendedorId)->value('name') ?? 'Vendedor';

        /* ================== TRANSAÇÃO ================== */
        $pagamentoId = DB::transaction(function () use (
            $empresaId, $vendedorId, $adminUserId, $mes, $dataPagto,
            $enriched, $ajustes, $totais, $salario, $totalRec, $percCom, $percImp
        ) {
            // Header
            $headerId = DB::table('comissao_pagamentos')->insertGetId([
                'empresa_id'           => $empresaId,
                'vendedor_id'          => $vendedorId,
                'mes'                  => $mes,
                'data_pagamento'       => $dataPagto,
                // snapshot do perfil (0 se só ajustes)
                'percentual_comissao'  => $percCom,
                'percentual_imposto'   => $percImp,
                'total_bruto'          => $totais['bruto'],
                'total_imposto'        => $totais['imposto'],
                'total_liquido'        => $totais['liquido'],
                'salario'              => 0,              // <<< zera no header
                'total_receber'        => $totalRec,      // <<< só líquido
                'created_by'           => $adminUserId,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // Itens de VENDAS
            if ($enriched->isNotEmpty()) {
                $itensV = $enriched->map(function ($r) use ($headerId) {
                    return [
                        'comissao_pagamento_id' => $headerId,
                        'venda_id'              => $r->id,
                        'ajuste_id'             => null,
                        'is_adicional'          => 0,
                        'tipo_lancamento'       => 'OUTRO',
                        'descricao'             => null,
                        'valor_contrato'        => $r->valor_base_para_item,
                        'percentual'            => $r->percentual,
                        'imposto_perc'          => $r->imposto_perc,
                        'bruto'                 => $r->bruto,
                        'imposto_valor'         => $r->imposto_valor,
                        'liquido'               => $r->liquido,
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                })->toArray();

                DB::table('comissao_pagamento_itens')->insert($itensV);

                // marca vendas como pagas
                DB::table('vendas')
                    ->whereIn('id', $enriched->pluck('id'))
                    ->update([
                        'comissao_paga'            => 1,
                        'data_pagamento_comissao'  => now(),
                        'updated_at'               => now(),
                    ]);
            }

            // Itens de AJUSTES
            if ($ajustes->isNotEmpty()) {
                $itensA = $ajustes->map(function ($a) use ($headerId) {
                    $tipo = in_array($a->categoria, ['MOTIVACIONAL','AJUSTE','BONUS','OUTRO', 'ANGARIACAO']) ? $a->categoria : 'AJUSTE';

                    return [
                        'comissao_pagamento_id' => $headerId,
                        'venda_id'              => null,
                        'ajuste_id'             => $a->id,
                        'is_adicional'          => 1,
                        'tipo_lancamento'       => $tipo,
                        'descricao'             => $a->descricao,
                        'valor_contrato'        => (float) $a->valor_bruto,
                        'percentual'            => 0,
                        'imposto_perc'          => (float) $a->imposto_perc,
                        'bruto'                 => (float) $a->valor_bruto,
                        'imposto_valor'         => (float) $a->imposto_valor,
                        'liquido'               => (float) $a->valor_liquido, // pode ser negativo (débito)
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                })->toArray();

                DB::table('comissao_pagamento_itens')->insert($itensA);

                DB::table('lancamentos_debito_credito')
                    ->whereIn('id', $ajustes->pluck('id'))
                    ->update([
                        'status'                 => 'pago',
                        'comissao_pagamento_id'  => $headerId,
                        'pago_em'                => now(),
                        'updated_at'             => now(),
                    ]);
            }

            return $headerId;
        });

        return response()->json([
            'message'               => "Pagamento registrado para {$vendedor}.",
            'pagamento_id'          => $pagamentoId,
            'pdf_url'               => route('comissionamento.pagamento.pdf', $pagamentoId),
            'totais'                => $totais,
            'salario'               => 0.0,                // <<< retorna 0
            'percentual_comissao'   => $percCom,
            'percentual_imposto'    => $percImp,
            'total_receber'         => $totalRec,          // <<< líquido
        ]);
    }

    public function pdfPagamento($pagamentoId)
    {
        $empresaId = Auth::user()->empresa_id;

        $p = DB::table('comissao_pagamentos as p')
            ->join('users as u', 'u.id', '=', 'p.vendedor_id')
            ->where('p.empresa_id', $empresaId)
            ->where('p.id', $pagamentoId)
            ->select('p.*', 'u.name as vendedor')
            ->first();


        $gradeAtual = DB::table('comissionamento_configuracao')
            ->where('user_id', $p->vendedor_id)
            ->select(DB::raw('LOWER(grade) as grade'))
            ->value('grade');

        abort_if(!$p, 404);

        // Itens (Vendas + Ajustes)
        $itens = DB::table('comissao_pagamento_itens as i')
            ->leftJoin('vendas as v', 'v.id', '=', 'i.venda_id')
            ->leftJoin('lancamentos_debito_credito as a', 'a.id', '=', 'i.ajuste_id')
            ->where('i.comissao_pagamento_id', $pagamentoId)
            ->select([
                'i.*',
                'v.nome_contrato',
                'v.data_implantacao',
                'v.angariacao_valor',
                'v.angariacao_status',
                'v.operadora',
                'a.natureza as ajuste_natureza',
                'a.categoria as ajuste_categoria',
                'a.descricao as ajuste_descricao',
            ])
            ->orderByRaw('COALESCE(v.data_implantacao, i.created_at) asc')
            ->get();

        // Linhas para a view
        $linhas = $itens->map(function ($i) {
            $isAjuste = !is_null($i->ajuste_id) || is_null($i->venda_id);

            if ($isAjuste) {
                return (object) [
                    'is_ajuste'         => true,
                    'tipo_label'        => strtoupper($i->ajuste_natureza ?? '') === 'DEBITO' ? 'Ajuste (Débito)' : 'Ajuste (Crédito)',
                    'tipo_categoria'    => $i->ajuste_categoria,
                    'descricao'         => $i->ajuste_descricao,
                    'data_implantacao'  => null,
                    'nome_contrato'     => sprintf(
                        '%s · %s%s',
                        strtoupper($i->ajuste_natureza ?? 'AJUSTE'),
                        strtoupper($i->ajuste_categoria ?? 'OUTRO'),
                        $i->ajuste_descricao ? (' — ' . $i->ajuste_descricao) : ''
                    ),
                    'operadora'         => "-",
                    'valor_contrato'    => (float) $i->valor_contrato,
                    'percentual'        => null,
                    'bruto'             => (float) $i->bruto,
                    'imposto_valor'     => (float) $i->imposto_valor,
                    'liquido'           => (float) $i->liquido, // pode ser negativo
                    'angariacao_valor'  => 0,
                    'angariacao_status' => null,
                ];
            }

            // Venda
            return (object) [
                'is_ajuste'         => false,
                'tipo_label'        => (strtoupper((string) $i->angariacao_status) === 'SIM') ? 'Angariação' : 'Venda',
                'tipo_categoria'    => null,
                'descricao'         => null,
                'data_implantacao'  => $i->data_implantacao,
                'nome_contrato'     => $i->nome_contrato,
                'operadora'         => $i->operadora,
                'valor_contrato'    => (float) $i->valor_contrato, // base usada no cálculo
                'percentual'        => (float) $i->percentual,
                'bruto'             => (float) $i->bruto,
                'imposto_valor'     => (float) $i->imposto_valor,
                'liquido'           => (float) $i->liquido,
                'angariacao_valor'  => (float) $i->angariacao_valor,
                'angariacao_status' => $i->angariacao_status,
            ];
        });

        // Totais do header
        $totais = [
            'bruto'   => (float) $p->total_bruto,
            'imposto' => (float) $p->total_imposto,
            'liquido' => (float) $p->total_liquido,
        ];

        // Quebra Vendas vs Ajustes
        $linVendas  = $linhas->where('is_ajuste', false);
        $linAjustes = $linhas->where('is_ajuste', true);

        $totVendas = [
            'bruto'   => (float) $linVendas->sum('bruto'),
            'imposto' => (float) $linVendas->sum('imposto_valor'),
            'liquido' => (float) $linVendas->sum('liquido'),
        ];

        $totAjustes = [
            'bruto'    => (float) $linAjustes->sum('bruto'),
            'imposto'  => (float) $linAjustes->sum('imposto_valor'),
            'liquido'  => (float) $linAjustes->sum('liquido'), // com sinal
            'creditos' => (float) $linAjustes->filter(fn($r) => $r->liquido > 0)->sum('liquido'),
            'debitos'  => (float) abs($linAjustes->filter(fn($r) => $r->liquido < 0)->sum('liquido')),
        ];

        // Perfil para exibição (salário fora do PDF)
        $perfil = [
            'grade'      => $gradeAtual,
            'percentual' => (float) $p->percentual_comissao,
            'salario'    => 0.0, // não exibir salário
            'imposto'    => (float) $p->percentual_imposto,
        ];

        // Período
        $periodo = Carbon::createFromFormat('Y-m-d', "{$p->mes}-01")
            ->locale('pt_BR')
            ->isoFormat('MMMM [de] YYYY');

        // >>> Data de pagamento formatada (flag)
        $pagoEmFmt = $p->pago_em ? Carbon::parse($p->pago_em)->format('d/m/Y') : null;

        // >>> Conta de pagamento (do registro ou padrão do vendedor)
        $conta = null;
        if (!empty($p->conta_pagamento_id)) {
            $conta = DB::table('contas_pagamento')
                ->where('id', $p->conta_pagamento_id)
                ->where('user_id', $p->vendedor_id)
                ->first();
        }
        if (!$conta) {
            $conta = DB::table('contas_pagamento')
                ->where('user_id', $p->vendedor_id)
                ->where('is_default', 1)
                ->first();
        }

        $agenciaConta = $conta
            ? trim(($conta->agencia ?? '') . ' / ' . ($conta->conta ?? '') . (!empty($conta->digito) ? '-' . $conta->digito : ''))
            : null;
        $chavePix = $conta->chave_pix ?? null;

        $pdf = Pdf::loadView('pdf.comissionamento-vendedor', [
            'mes'           => $p->mes,
            'periodo'       => mb_convert_case($periodo, MB_CASE_TITLE, 'UTF-8'),
            'vendedor'      => $p->vendedor,
            'linhas'        => $linhas,
            'totais'        => $totais,
            'totVendas'     => $totVendas,
            'totAjustes'    => $totAjustes,
            'perfil'        => $perfil,
            'totalReceber'  => (float) $p->total_liquido, // somente líquido

            // >>> novos dados para a view
            'pagoEm'        => $pagoEmFmt,     // "DD/MM/AAAA" ou null
            'agenciaConta'  => $agenciaConta,  // string ou null
            'chavePix'      => $chavePix,      // string ou null
            'empresaNome' => 'LK´BROKERS consultoria em seguros',
            'empresaCnpj' => '42.294.064/0001-92',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("pagamento_comissao_{$p->mes}_{$p->vendedor}.pdf");
    }



    public function pagamentosIndex()
    {
        $empresaId = auth()->user()->empresa_id;

        if (Auth::user()->user_role_id === UserRole::VENDEDOR) {
            $vendedores = DB::table('users as u')
                ->join('comissao_pagamentos as p', 'p.vendedor_id', '=', 'u.id')
                ->where('p.empresa_id', $empresaId)
                ->where('u.id', Auth::user()->id)
                ->select('u.id', 'u.name')
                ->distinct()
                ->orderBy('u.name')
                ->get();
        } else {
            $vendedores = DB::table('users as u')
                ->join('comissao_pagamentos as p', 'p.vendedor_id', '=', 'u.id')
                ->where('p.empresa_id', $empresaId)
                ->select('u.id', 'u.name')
                ->distinct()
                ->orderBy('u.name')
                ->get();
        }

        // quem criou (admins)
        $criadores = DB::table('users as u')
            ->join('comissao_pagamentos as p', 'p.created_by', '=', 'u.id')
            ->where('p.empresa_id', $empresaId)
            ->select('u.id', 'u.name')
            ->distinct()
            ->orderBy('u.name')
            ->get();

        return view('content.pages.comissionamento.pagamentos-index', compact('vendedores', 'criadores'));
    }

    public function pagamentosData(Request $request)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;

        $isVendedor = $user->user_role_id == UserRole::VENDEDOR;

        $mes = $request->input('mes');
        $vendedorId = $request->integer('vendedor_id');
        $criadorId = $request->integer('created_by');

        $q = DB::table('comissao_pagamentos as p')
            ->join('users as v', 'v.id', '=', 'p.vendedor_id')
            ->join('users as c', 'c.id', '=', 'p.created_by')
            ->where('p.empresa_id', $empresaId);

        if ($isVendedor) {
            $q->where('p.vendedor_id', $user->id);
        } else {
            if (!empty($vendedorId)) {
                $q->where('p.vendedor_id', $vendedorId);
            }
            if (!empty($criadorId)) {
                $q->where('p.created_by', $criadorId);
            }
        }

        if (!empty($mes)) {
            $q->where('p.mes', $mes);
        }

        $rows = $q->select([
            'p.id',
            'p.mes',
            'p.data_pagamento',
            'p.percentual_comissao',
            'p.percentual_imposto',
            'p.total_bruto',
            'p.total_imposto',
            'p.total_liquido',
            'p.salario',
            'p.total_receber',
            'p.pago_em',
            'p.vendedor_id',
            'p.conta_pagamento_id',
            'v.name as vendedor',
            'c.name as criado_por',
            'p.created_at',
        ])
            ->orderByDesc('p.data_pagamento')
            ->orderByDesc('p.id')
            ->get();

        return response()->json(['data' => $rows]);
    }


    public function pagamentosEstornar($id)
    {
        $empresaId = auth()->user()->empresa_id;

        // carrega header
        $header = DB::table('comissao_pagamentos')
            ->where('empresa_id', $empresaId)
            ->where('id', $id)
            ->first();

        if (!$header) {
            return response()->json(['message' => 'Pagamento não encontrado.'], 404);
        }

        DB::transaction(function () use ($id) {
            $vendaIds = DB::table('comissao_pagamento_itens')
                ->where('comissao_pagamento_id', $id)
                ->pluck('venda_id');

            DB::table('vendas')->whereIn('id', $vendaIds)->update([
                'comissao_paga' => 0,
                'data_pagamento_comissao' => null,
                'updated_at' => now(),
            ]);

            DB::table('comissao_pagamento_itens')->where('comissao_pagamento_id', $id)->delete();
            DB::table('comissao_pagamentos')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Pagamento estornado com sucesso.']);
    }

    public function byUser(Request $request) {

        $userId = (int) $request->query('user_id');
        $contas = ContaPagamento::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->get(['id','banco','agencia','conta','digito','chave_pix','descricao','is_default']);
        return response()->json($contas);
    }

    public function pagar(Request $request, int $id) {
        $pag = ComissaoPagamento::findOrFail($id);

        $pagoEm  = \Carbon\Carbon::parse($request->input('pago_em', now('America/Sao_Paulo')))->toDateString();
        $contaId = $request->input('conta_pagamento_id');

        if (!$contaId) {
            $contaId = ContaPagamento::where('user_id', $pag->user_id)->where('is_default', true)->value('id');
        }

        $pag->update([
            'pago_em'            => $pagoEm,
            'conta_pagamento_id' => $contaId,
        ]);

        return response()->json([
            'ok'                  => true,
            'pago_em'             => $pag->pago_em,
            'conta_pagamento_id'  => $pag->conta_pagamento_id,
        ]);
    }


}
