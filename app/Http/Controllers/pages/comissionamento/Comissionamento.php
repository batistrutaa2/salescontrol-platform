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
    public function getFaturamentoComissionamento(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $mes = $request->input('mes') ?: Carbon::now('America/Sao_Paulo')->format('Y-m');
        [$y, $m] = explode('-', $mes);

        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();
        $vendedorId = $request->input('vendedor_id');

        // ========== BASE DE VENDAS ==========
        // Vendas implantadas no período, com comissão AINDA não paga, e com config de comissionamento
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
                DB::raw('LOWER(cfg.grade) as grade'), // normaliza: junior/senior/admin/comercial
                'cfg.imposto',
                'cfg.salario',
                'cfg.percentual', // mantido para eventual uso futuro
            ])
            ->orderBy('u.name')
            ->orderBy('v.data_implantacao')
            ->get();

        // ========== CÁLCULOS (JÚNIOR/SÊNIOR) ==========
        $porVendedor = [];
        $kpiContratos = 0;
        $kpiTotalContratos = 0.0;
        $kpiTotalComissao = 0.0;

        $totalVendasAllGrades = 0.0; // base ADMIN (5% sobre TODAS as vendas do período)
        $totalVendasJunior = 0.0; // base COMERCIAL (somente vendas de JUNIOR)

        foreach ($rows as $r) {
            $valor = (float) $r->valor_contrato;
            $imposto = (float) $r->imposto;
            $grade = strtolower((string) $r->grade);
            $angariacaoValor = (float) $r->angariacao_valor;

            if ($r->percentual > 0) {
                $valorComissaoLiquida = 0.0;

                if ($r->angariacao_status == 'SIM') {
                    $valorComissaoLiquida = $angariacaoValor * 0.5;
                    $totalVendasAllGrades += $angariacaoValor;

                    if ($grade == 'junior') {
                        $totalVendasJunior += $angariacaoValor;
                    }
                } else {
                    $valorComissaoBruta = $valor * ($r->percentual / 100.0);
                    $valorComissaoLiquida = $valorComissaoBruta * (1.0 - ($imposto / 100.0));
                    $totalVendasAllGrades += $valor;

                    if ($grade == 'junior') {
                        $totalVendasJunior += $valor;
                    }
                }

                $porVendedor[$r->user_id] ??= [
                    'user_id' => $r->user_id,
                    'vendedor' => $r->vendedor,
                    'percentual' => $r->percentual,
                    'totais' => ['qtd' => 0, 'contratos' => 0.0, 'comissao' => 0.0],
                    'contratos' => [],
                ];

                $porVendedor[$r->user_id]['contratos'][] = [
                    'id' => $r->id,
                    'nome_contrato' => $r->nome_contrato,
                    'valor_contrato' => round($valor, 2),
                    'valor_comissao' => round($valorComissaoLiquida, 2),
                    'data_implantacao' => Carbon::parse($r->data_implantacao)->format('d/m/Y'),
                    'angariacao_valor' => round($angariacaoValor, 2),
                    'angariacao_status' => $r->angariacao_status,
                ];

                $porVendedor[$r->user_id]['totais']['qtd'] += 1;
                $porVendedor[$r->user_id]['totais']['contratos'] += $valor;
                $porVendedor[$r->user_id]['totais']['comissao'] += $valorComissaoLiquida;

                $kpiContratos += 1;
                $kpiTotalContratos += $valor;
                $kpiTotalComissao += $valorComissaoLiquida;
            }
        }


        // ========== ADMIN (5% sobre TODAS as vendas), com imposto individual ==========
        $admins = DB::table('comissionamento_configuracao as cfg')
            ->join('users as u', 'u.id', '=', 'cfg.user_id')
            ->where('cfg.empresa_id', $empresaId)
            ->whereRaw('LOWER(cfg.grade) = "admin"')
            ->select('cfg.user_id', 'u.name as nome', 'cfg.imposto', 'cfg.percentual')
            ->get();


        $adminPercent = 5.0;
        $adminUsuarios = [];

        foreach ($admins as $ad) {
            $bruta = $totalVendasAllGrades * ($ad->percentual / 100.0);
            $liquida = $bruta * (1.0 - ((float) $ad->imposto / 100.0));
            $adminUsuarios[] = [
                'user_id' => $ad->user_id,
                'nome' => $ad->nome,
                'percentual_base' => $ad->percentual,
                'comissao_bruta' => round($bruta, 2),
                'comissao_liquida' => round($liquida, 2),
                'imposto' => (float) $ad->imposto,
            ];
        }
        // ========== COMERCIAL (supervisores) – REGRA CORRETA ==========
        // Base = soma das VENDAS dos JUNIOR no período (totalVendasJunior)
        // Deduz: SOMA dos SALÁRIOS de TODOS os JUNIOR + 5% custo adm
        // Resultado (pool) dividido IGUALMENTE entre os supervisores (grade 'comercial'), SEM imposto.
        $salariosJuniorTot = (float) DB::table('comissionamento_configuracao')
            ->where('empresa_id', $empresaId)
            ->whereRaw('LOWER(grade) = "junior"')
            ->sum('salario');

        $custoAdm5 = $totalVendasJunior * 0.05;

        $poolFinal = $totalVendasJunior - $salariosJuniorTot - $custoAdm5;
        $desconto = $poolFinal * 0.10;
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
                'nome' => $g->nome,
                'quota' => round($quota, 2), // sem imposto
            ];
        }

        // ========== PAYLOAD ==========
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
            // JUNIOR/SENIOR para a UI atual
            'vendedores' => array_values($porVendedor),

            // Bloco por grades
            'grades' => [
                'bases' => [
                    'total_vendas_all_grades' => round($totalVendasAllGrades, 2),
                    'total_vendas_junior' => round($totalVendasJunior, 2),
                ],
                'admin' => [
                    'percentual' => $adminPercent,
                    'usuarios' => $adminUsuarios,
                    'total_liquido' => round(array_sum(array_column($adminUsuarios, 'comissao_liquida')), 2),
                ],
                'comercial' => [
                    'qtd_gestores' => $qtdGestores,
                    'base_junior' => round($totalVendasJunior, 2),
                    'salarios_junior_tot' => round($salariosJuniorTot, 2),
                    'custo_admin_5' => round($custoAdm5, 2),
                    'pool_final' => round($poolFinal, 2),
                    'quota' => round($quota, 2),
                    'gestores' => $gestoresArr,
                    'total_distribuido' => round($quota * $qtdGestores, 2),
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


    public function pagarVendedor(Request $request)
    {
        $request->validate([
            'mes' => ['required', 'regex:/^\d{4}\-\d{2}$/'],
            'vendedor_id' => ['required', 'integer'],
            'data_pagamento' => ['required', 'date'],
            'venda_ids' => ['required', 'array', 'min:1'],
            'venda_ids.*' => ['integer'],
        ]);

        $empresaId = Auth::user()->empresa_id;
        $adminUserId = Auth::id();
        $vendedorId = (int) $request->integer('vendedor_id');
        $mes = $request->input('mes');

        [$y, $m] = explode('-', $mes);
        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();

        $ids = array_map('intval', $request->input('venda_ids', []));

        // Vendas elegíveis
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
            ->whereIn('v.id', $ids)
            ->select([
                'v.id',
                'v.user_id',
                'u.name as vendedor',
                DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
                'v.data_implantacao',
                DB::raw('LOWER(cfg.grade) as grade'),
                'cfg.imposto',
                'cfg.salario',
                'cfg.percentual',
            ])
            ->orderBy('v.data_implantacao')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Nenhuma venda elegível para pagamento.'], 422);
        }

        // Congela parâmetros e calcula por item
        $enriched = $rows->map(function ($r) {
            $valor = (float) $r->valor_contrato;
            $perc = isset($r->percentual) ? (float) $r->percentual : 0.0;
            $impP = isset($r->imposto) ? (float) $r->imposto : 10.0;

            $bruto = $valor * ($perc / 100);
            $impVal = $bruto * ($impP / 100);
            $liquido = $bruto - $impVal;

            $r->percentual = $perc;
            $r->imposto_perc = $impP;
            $r->bruto = $bruto;
            $r->imposto_valor = $impVal;
            $r->liquido = $liquido;
            return $r;
        });

        $totais = [
            'bruto' => (float) $enriched->sum('bruto'),
            'imposto' => (float) $enriched->sum('imposto_valor'),
            'liquido' => (float) $enriched->sum('liquido'),
        ];

        // Snapshot de config vigente (pega do primeiro registro)
        $cfg = $rows->first();
        $salario = (float) ($cfg->salario ?? 0);
        $percCom = (float) ($cfg->percentual ?? 0);
        $percImp = (float) ($cfg->imposto ?? 10);
        $totalRec = $salario + $totais['liquido'];
        $vendedor = $rows->first()->vendedor ?? 'Vendedor';

        // Transação: grava header + itens + marca vendas
        $pagamentoId = DB::transaction(function () use ($empresaId, $vendedorId, $adminUserId, $mes, $request, $enriched, $totais, $salario, $totalRec, $percCom, $percImp) {
            $headerId = DB::table('comissao_pagamentos')->insertGetId([
                'empresa_id' => $empresaId,
                'vendedor_id' => $vendedorId,
                'mes' => $mes,
                'data_pagamento' => \Carbon\Carbon::parse($request->input('data_pagamento'))->toDateString(),
                'percentual_comissao' => $percCom,
                'percentual_imposto' => $percImp,
                'total_bruto' => $totais['bruto'],
                'total_imposto' => $totais['imposto'],
                'total_liquido' => $totais['liquido'],
                'salario' => $salario,
                'total_receber' => $totalRec,
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $itens = $enriched->map(function ($r) use ($headerId) {
                return [
                    'comissao_pagamento_id' => $headerId,
                    'venda_id' => $r->id,
                    'valor_contrato' => $r->valor_contrato,
                    'percentual' => $r->percentual,
                    'imposto_perc' => $r->imposto_perc,
                    'bruto' => $r->bruto,
                    'imposto_valor' => $r->imposto_valor,
                    'liquido' => $r->liquido,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DB::table('comissao_pagamento_itens')->insert($itens);

            DB::table('vendas')
                ->whereIn('id', $enriched->pluck('id'))
                ->update([
                    'comissao_paga' => 1,
                    'data_pagamento_comissao' => now(),
                    // se você criou a coluna opcional comissao_pagamento_id:
                    // 'comissao_pagamento_id' => $headerId,
                    'updated_at' => now(),
                ]);

            return $headerId;
        });

        return response()->json([
            'message' => "Pagamento registrado para {$vendedor}.",
            'pagamento_id' => $pagamentoId,
            'pdf_url' => route('comissionamento.pagamento.pdf', $pagamentoId),
            'totais' => $totais,
            'salario' => $salario,
            'percentual_comissao' => $percCom,
            'percentual_imposto' => $percImp,
            'total_receber' => $totalRec,
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

        abort_if(!$p, 404);

        $itens = DB::table('comissao_pagamento_itens as i')
            ->join('vendas as v', 'v.id', '=', 'i.venda_id')
            ->where('i.comissao_pagamento_id', $pagamentoId)
            ->select('i.*', 'v.nome_contrato', 'v.data_implantacao', 'v.angariacao_valor', 'v.angariacao_status')
            ->orderBy('v.data_implantacao')
            ->get();

        $linhas = $itens->map(function ($i) {
            return (object) [
                'data_implantacao' => $i->data_implantacao,
                'nome_contrato' => $i->nome_contrato,
                'valor_contrato' => $i->valor_contrato,
                'percentual' => $i->percentual,
                'bruto' => $i->bruto,
                'imposto_valor' => $i->imposto_valor,
                'liquido' => $i->liquido,
                'angariacao_valor' => $i->angariacao_valor,
                'angariacao_status' => $i->angariacao_status,
            ];
        });

        $totais = [
            'bruto' => (float) $p->total_bruto,
            'imposto' => (float) $p->total_imposto,
            'liquido' => (float) $p->total_liquido,
        ];

        $perfil = [
            'grade' => null,
            'percentual' => $p->percentual_comissao,
            'salario' => (float) $p->salario,
            'imposto' => (float) $p->percentual_imposto,
        ];

        $periodo = Carbon::createFromFormat('Y-m-d', "{$p->mes}-01")->locale('pt_BR')->isoFormat('MMMM [de] YYYY');

        $pdf = Pdf::loadView('pdf.comissionamento-vendedor', [
            'mes' => $p->mes,
            'periodo' => mb_convert_case($periodo, MB_CASE_TITLE, 'UTF-8'),
            'vendedor' => $p->vendedor,
            'linhas' => $linhas,
            'totais' => $totais,
            'perfil' => $perfil,
            'totalReceber' => (float) $p->total_receber,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("pagamento_comissao_{$p->mes}_{$p->vendedor}.pdf");
    }

    public function pagamentosIndex()
    {
        $empresaId = auth()->user()->empresa_id;

        // carrega opções de vendedores para o select
        $vendedores = DB::table('users as u')
            ->join('comissao_pagamentos as p', 'p.vendedor_id', '=', 'u.id')
            ->where('p.empresa_id', $empresaId)
            ->select('u.id', 'u.name')
            ->distinct()
            ->orderBy('u.name')
            ->get();

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
        $empresaId = auth()->user()->empresa_id;

        $mes = $request->input('mes');         // 'YYYY-MM' (opcional)
        $vendedorId = $request->integer('vendedor_id');
        $criadorId = $request->integer('created_by');

        $q = DB::table('comissao_pagamentos as p')
            ->join('users as v', 'v.id', '=', 'p.vendedor_id')
            ->join('users as c', 'c.id', '=', 'p.created_by')
            ->where('p.empresa_id', $empresaId);

        if ($mes)
            $q->where('p.mes', $mes);
        if ($vendedorId)
            $q->where('p.vendedor_id', $vendedorId);
        if ($criadorId)
            $q->where('p.created_by', $criadorId);

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
            'v.name as vendedor',
            'c.name as criado_por',
            'p.created_at',
        ])
            ->orderByDesc('p.data_pagamento')
            ->orderByDesc('p.id')
            ->get();

        // retorno simples (client-side DataTable)
        return response()->json(['data' => $rows]);
    }

    // OPCIONAL: estorno simples (com validação mínima)
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
            // reabre vendas
            $vendaIds = DB::table('comissao_pagamento_itens')
                ->where('comissao_pagamento_id', $id)
                ->pluck('venda_id');

            DB::table('vendas')->whereIn('id', $vendaIds)->update([
                'comissao_paga' => 0,
                'data_pagamento_comissao' => null,
                // 'comissao_pagamento_id' => null, // se tiver essa coluna
                'updated_at' => now(),
            ]);

            // manter histórico do pagamento? duas opções:
            // 1) deletar header/itens:
            DB::table('comissao_pagamento_itens')->where('comissao_pagamento_id', $id)->delete();
            DB::table('comissao_pagamentos')->where('id', $id)->delete();

            // 2) marcar estornado (recomendado): adicione colunas estornado_at/by por migration
            // \DB::table('comissao_pagamentos')->where('id',$id)->update([
            //   'estornado_at'=> now(),
            //   'estornado_by'=> auth()->id(),
            // ]);
        });

        return response()->json(['message' => 'Pagamento estornado com sucesso.']);
    }

}
