<?php

namespace App\Http\Controllers\pages\comissionamento;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ComissaoPagamento;
use App\Models\ComissionamentoConfiguracoes;
use App\Models\ContaPagamento;
use App\Models\Empresa;
use App\Models\LancamentoDebitoCredito;
use App\Models\User;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Comissionamento extends Controller
{
    public function index()
    {
        $users = User::query()->tenantMember($this->activeEmpresaId())
            ->where('ativo', 'Y')
            ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])
            ->get();

        return view('content.pages.comissionamento.index', [
            'users' => $users,
        ]);
    }

    public function getCommissioning()
    {
        $empresaId = $this->activeEmpresaId();
        $configs = ComissionamentoConfiguracoes::with([
            'user' => fn ($query) => $query->where('empresa_id', $empresaId),
        ])
            ->where('empresa_id', $empresaId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $configs,
        ]);
    }

    public function store(Request $request)
    {
        $this->garantirGestaoComissionamento();
        $empresaId = $this->activeEmpresaId();
        $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('empresa_id', $empresaId)
                ->where('is_platform_admin', false)
                ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER]))],
            'percentual' => 'required|numeric|between:0,100',
            'percentual_angariacao' => 'required|numeric|between:0,100',
            'imposto' => 'required|numeric|between:0,100',
            'grade' => ['required', Rule::in(['JUNIOR', 'SENIOR', 'ADMIN', 'COMERCIAL'])],
            'periodicidade' => 'required|in:mensal,trimestral,semestral,anual',
        ]);

        // Converter salário do formato brasileiro (3.500,00) para decimal (3500.00)
        $salario = $request->salario;
        if (is_string($salario)) {
            $salario = str_replace('.', '', $salario); // Remove milhares
            $salario = str_replace(',', '.', $salario); // Troca vírgula por ponto
        }

        $dados = [
            'percentual' => $request->percentual,
            'percentual_angariacao' => $request->percentual_angariacao,
            'imposto' => $request->imposto,
            'grade' => $request->grade,
            'salario' => floatval($salario),
            'periodicidade' => $request->periodicidade,
        ];

        $comissao = ComissionamentoConfiguracoes::updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'user_id' => $request->user_id,
            ],
            $dados
        );

        return response()->json([
            'success' => true,
            'message' => $comissao->wasRecentlyCreated
                ? 'Comissão criada com sucesso.'
                : 'Comissão atualizada com sucesso.',
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->garantirGestaoComissionamento();
        $empresaId = $this->activeEmpresaId();
        $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('empresa_id', $empresaId)
                ->where('is_platform_admin', false)
                ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::ADMINISTRATIVO, UserRole::DEVELOPER]))],
            'percentual' => 'required|numeric|between:0,100',
            'percentual_angariacao' => 'required|numeric|between:0,100',
            'imposto' => 'required|numeric|between:0,100',
            'grade' => ['required', Rule::in(['JUNIOR', 'SENIOR', 'ADMIN', 'COMERCIAL'])],
            'periodicidade' => 'required|in:mensal,trimestral,semestral,anual',
        ]);

        $config = ComissionamentoConfiguracoes::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->firstOrFail();

        // Converter salário do formato brasileiro (3.500,00) para decimal (3500.00)
        $salario = $request->salario;
        if (is_string($salario)) {
            $salario = str_replace('.', '', $salario); // Remove milhares
            $salario = str_replace(',', '.', $salario); // Troca vírgula por ponto
        }

        $config->update([
            'user_id' => $request->user_id,
            'percentual' => $request->percentual,
            'percentual_angariacao' => $request->percentual_angariacao,
            'imposto' => $request->imposto,
            'grade' => $request->grade,
            'salario' => floatval($salario),
            'periodicidade' => $request->periodicidade,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comissão atualizada com sucesso.',
        ]);
    }

    public function destroy($id)
    {
        $this->garantirGestaoComissionamento();
        $config = ComissionamentoConfiguracoes::where('empresa_id', $this->activeEmpresaId())
            ->where('id', $id)
            ->firstOrFail();

        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuração de comissão excluída com sucesso.',
        ]);
    }

    public function invoiceCommission()
    {
        return view('content.pages.comissionamento.comissionamento-faturamento');
    }

    public function getVendedores(Request $request)
    {
        $empresaId = $this->activeEmpresaId();

        $vendedores = User::query()->tenantMember($empresaId)
            ->where('ativo', 'Y')
            ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::BACKOFFICE])
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'vendedores' => $vendedores,
        ]);
    }

    public function getFaturamentoComissionamento(Request $request)
    {
        $empresaId = $this->activeEmpresaId();
        $dados = $request->validate([
            'data_inicio' => ['nullable', 'date_format:Y-m-d', 'required_with:data_fim'],
            'data_fim' => ['nullable', 'date_format:Y-m-d', 'required_with:data_inicio', 'after_or_equal:data_inicio'],
            'mes' => ['nullable', 'date_format:Y-m'],
            'grade' => ['nullable', Rule::in(['junior', 'senior', 'admin', 'comercial'])],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('is_platform_admin', false)
                    ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::BACKOFFICE])),
            ],
        ]);

        // Aceita range de datas ou mês (para retrocompatibilidade)
        if (! empty($dados['data_inicio']) && ! empty($dados['data_fim'])) {
            $inicio = $dados['data_inicio'];
            $fim = $dados['data_fim'];
            // Calcula o mês de referência baseado na data de início
            $mes = Carbon::parse($inicio)->format('Y-m');
        } else {
            // Retrocompatibilidade com filtro por mês
            $mes = $dados['mes'] ?? Carbon::now('America/Sao_Paulo')->format('Y-m');
            [$y, $m] = explode('-', $mes);
            $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
            $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();
        }

        $vendedorId = $dados['vendedor_id'] ?? null;
        $grade = $dados['grade'] ?? null;

        // ========== BASE DE VENDAS ==========
        // Filtra vendas que têm algo pendente: comissão OU angariação não paga
        $rows = DB::table('vendas as v')
            ->join('users as u', function ($join) {
                $join->on('u.id', '=', 'v.user_id')->on('u.empresa_id', '=', 'v.empresa_id')->where('u.is_platform_admin', false);
            })
            ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
                $j->on('cfg.user_id', '=', 'v.user_id')
                    ->where('cfg.empresa_id', '=', $empresaId);
            })
            ->where('v.empresa_id', $empresaId)
            ->whereBetween('v.data_implantacao', [$inicio, $fim])
            ->whereNotNull('v.data_implantacao')
            // Vendas que têm algo pendente (comissão OU angariação)
            ->where(function ($q) {
                $q->where('v.comissao_paga', 0)  // Comissão pendente
                    ->orWhere(function ($q2) {
                        $q2->whereRaw("UPPER(v.angariacao_status) = 'SIM'")
                            ->where('v.angariacao_paga', 0);  // Angariação pendente
                    });
            })
            ->when($vendedorId, fn ($q) => $q->where('v.user_id', $vendedorId))
            ->when($grade, fn ($q) => $q->whereRaw('LOWER(cfg.grade) = ?', [$grade]))
            ->select([
                'v.id',
                'v.user_id',
                'u.name as vendedor',
                'v.nome_contrato',
                'v.operadora',
                'v.angariacao_valor',
                'v.angariacao_status',
                DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
                'v.data_implantacao',
                DB::raw('LOWER(cfg.grade) as grade'),
                'cfg.imposto',
                'cfg.salario',
                'cfg.percentual',
                'cfg.percentual_angariacao',
                'v.comissao_paga',
                'v.angariacao_paga',
            ])
            ->orderBy('u.name')
            ->orderBy('v.data_implantacao')
            ->get();

        // ========== CÁLCULOS ==========
        $porVendedor = [];
        $kpiContratos = 0;
        $kpiTotalContratos = 0.0;
        $kpiTotalComissao = 0.0;

        foreach ($rows as $r) {
            $valor = (float) $r->valor_contrato;
            $impostoCfg = (float) $r->imposto;
            $angVal = (float) $r->angariacao_valor;
            $percentualAngariacao = (float) $r->percentual_angariacao;
            $isAng = strtoupper((string) $r->angariacao_status) === 'SIM';

            // Inicializa vendedor se não existir
            if (! isset($porVendedor[$r->user_id])) {
                $porVendedor[$r->user_id] = [
                    'user_id' => $r->user_id,
                    'vendedor' => $r->vendedor,
                    'percentual' => (float) $r->percentual,
                    'grade' => $r->grade,
                    'totais' => ['qtd' => 0, 'contratos' => 0.0, 'comissao' => 0.0],
                    'contratos' => [],
                ];
            }

            // ITEM 1: COMISSÃO (se ainda não pago)
            if (! $r->comissao_paga) {
                $comissaoBruta = round($valor * ((float) $r->percentual / 100.0), 2);
                $comissaoLiquida = round($comissaoBruta * (1.0 - ($impostoCfg / 100.0)), 2);

                $porVendedor[$r->user_id]['contratos'][] = [
                    'id' => $r->id,
                    'tipo_item' => 'COMISSAO',
                    'is_ajuste' => false,
                    'nome_contrato' => $r->nome_contrato,
                    'operadora' => $r->operadora ?? '-',
                    'valor_contrato' => round($valor, 2),
                    'valor_base' => round($valor, 2),
                    'percentual_aplicado' => round((float) $r->percentual, 2),
                    'imposto_aplicado' => round($impostoCfg, 2),
                    'valor_comissao_bruta' => $comissaoBruta,
                    'valor_comissao' => $comissaoLiquida,
                    'data_implantacao' => Carbon::parse($r->data_implantacao)->format('d/m/Y'),
                    'angariacao_valor' => 0,
                    'angariacao_status' => 'NAO',
                ];

                $porVendedor[$r->user_id]['totais']['qtd'] += 1;
                $porVendedor[$r->user_id]['totais']['contratos'] += $valor;
                $porVendedor[$r->user_id]['totais']['comissao'] += $comissaoLiquida;

                $kpiContratos += 1;
                $kpiTotalContratos += $valor;
                $kpiTotalComissao += $comissaoLiquida;
            }

            // ITEM 2: ANGARIAÇÃO (se tem angariação E ainda não pago)
            if ($isAng && ! $r->angariacao_paga) {
                $angBruta = round($angVal * ($percentualAngariacao / 100.0), 2);
                $angLiquida = $angBruta; // Sem imposto

                $porVendedor[$r->user_id]['contratos'][] = [
                    'id' => $r->id,
                    'tipo_item' => 'ANGARIACAO',
                    'is_ajuste' => false,
                    'nome_contrato' => $r->nome_contrato.' (Angariação)',
                    'operadora' => $r->operadora ?? '-',
                    'valor_contrato' => round($angVal, 2),
                    'valor_base' => round($angVal, 2),
                    'percentual_aplicado' => round($percentualAngariacao, 2),
                    'imposto_aplicado' => 0,
                    'valor_comissao_bruta' => $angBruta,
                    'valor_comissao' => $angLiquida,
                    'data_implantacao' => Carbon::parse($r->data_implantacao)->format('d/m/Y'),
                    'angariacao_valor' => round($angVal, 2),
                    'angariacao_status' => 'SIM',
                ];

                $porVendedor[$r->user_id]['totais']['qtd'] += 1;
                $porVendedor[$r->user_id]['totais']['contratos'] += $angVal;
                $porVendedor[$r->user_id]['totais']['comissao'] += $angLiquida;

                $kpiContratos += 1;
                $kpiTotalContratos += $angVal;
                $kpiTotalComissao += $angLiquida;
            }
        }

        /* ===== AJUSTES (pendentes) para a empresa, e opcionalmente vendedor ===== */
        // Filtra ajustes pelo período selecionado (campo 'mes' em formato YYYY-MM)
        // Converte data_inicio e data_fim para YYYY-MM para comparar com o campo 'mes'
        $mesInicio = substr($inicio, 0, 7); // '2025-03-01' -> '2025-03'
        $mesFim = substr($fim, 0, 7);       // '2025-03-31' -> '2025-03'

        $ajustes = DB::table('lancamentos_debito_credito as a')
            ->join('users as u', function ($join) {
                $join->on('u.id', '=', 'a.vendedor_id')->on('u.empresa_id', '=', 'a.empresa_id')->where('u.is_platform_admin', false);
            })
            ->leftJoin('comissionamento_configuracao as cfg', function ($join) {
                $join->on('cfg.user_id', '=', 'a.vendedor_id')->on('cfg.empresa_id', '=', 'a.empresa_id');
            })
            ->where('a.empresa_id', $empresaId)
            ->where('a.status', 'pendente')
            ->whereBetween('a.mes', [$mesInicio, $mesFim])
            ->when($vendedorId, fn ($q) => $q->where('a.vendedor_id', $vendedorId))
            ->when($grade, fn ($q) => $q->whereRaw('LOWER(cfg.grade) = ?', [$grade]))
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
                'a.mes', // Incluir o mês para referência no frontend
                'a.parcelado',
                'a.parcela_atual',
                'a.parcelas_total',
                'a.valor_total_original',
                DB::raw('LOWER(cfg.grade) as grade'),
            ])
            ->orderBy('u.name')
            ->get();

        foreach ($ajustes as $aj) {
            if (! isset($porVendedor[$aj->user_id])) {
                $porVendedor[$aj->user_id] = [
                    'user_id' => $aj->user_id,
                    'vendedor' => $aj->vendedor,
                    'percentual' => 0.0,
                    'grade' => $aj->grade,
                    'totais' => ['qtd' => 0, 'contratos' => 0.0, 'comissao' => 0.0],
                    'contratos' => [],
                ];
            }

            // Monta o nome sintético com informação de parcela
            $parcela = ($aj->parcelado && $aj->parcela_atual && $aj->parcelas_total)
                ? sprintf(' [%d/%d]', $aj->parcela_atual, $aj->parcelas_total)
                : '';

            $nomeSintetico = ($aj->natureza === 'CREDITO' ? 'Crédito' : 'Despesa')
                .' · '.ucfirst(strtolower($aj->categoria))
                .($aj->descricao ? ' — '.$aj->descricao : '')
                .$parcela;

            $porVendedor[$aj->user_id]['contratos'][] = [
                'id' => $aj->id,
                'is_ajuste' => true,                 // FLAG para o front
                'nome_contrato' => $nomeSintetico,
                'valor_contrato' => round((float) $aj->valor_bruto, 2),
                'valor_base' => round((float) $aj->valor_bruto, 2),
                'percentual_aplicado' => 0,
                'imposto_aplicado' => round((float) $aj->imposto_perc, 2),
                'valor_comissao_bruta' => round((float) $aj->valor_bruto, 2),
                'valor_comissao' => round((float) $aj->valor_liquido, 2), // LÍQUIDO COM SINAL
                'data_implantacao' => '',    // não se aplica
                'angariacao_valor' => 0,
                'angariacao_status' => 'NAO',
                'categoria' => $aj->categoria,
                'mes_lancamento' => $aj->mes, // Mês de referência do lançamento
                'parcelado' => (bool) $aj->parcelado,
                'parcela_atual' => (int) ($aj->parcela_atual ?? 0),
                'parcelas_total' => (int) ($aj->parcelas_total ?? 0),
                'valor_total_original' => round((float) ($aj->valor_total_original ?? 0), 2),
            ];

            // Ajuste entra SOMENTE na soma da comissão líquida (com sinal)
            $porVendedor[$aj->user_id]['totais']['comissao'] += (float) $aj->valor_liquido;
            $kpiTotalComissao += (float) $aj->valor_liquido;
        }

        // (Opcional) ordenar vendedores por nome depois de injetar ajustes
        if (! empty($porVendedor)) {
            $porVendedor = array_values(
                collect($porVendedor)->sortBy('vendedor', SORT_NATURAL | SORT_FLAG_CASE)->toArray()
            );
        } else {
            $porVendedor = [];
        }

        // ========== PAYLOAD ==========
        $payload = [
            'empresa_id' => $empresaId,
            'mes' => $mes,
            'filtro' => [
                'data_inicio' => $inicio,
                'data_fim' => $fim,
                'inicio' => $inicio, // Mantido para retrocompatibilidade
                'fim' => $fim,    // Mantido para retrocompatibilidade
                'vendedor_id' => $vendedorId ? (int) $vendedorId : null,
                'grade' => $grade,
            ],
            'kpis' => [
                'vendedores' => count($porVendedor),
                'contratos' => $kpiContratos,
                'total_contratos' => round($kpiTotalContratos, 2),
                'total_comissao' => round($kpiTotalComissao, 2),
            ],
            'vendedores' => $porVendedor, // já ordenado
        ];

        return response()->json($payload);
    }

    public function sellerCommission()
    {
        return view('content.pages.comissionamento.comissionamento-vendedor');
    }

    public function getCommissioningBySeller(Request $request)
    {
        $empresaId = $this->activeEmpresaId();
        $userId = Auth::id();

        $dados = $request->validate(['mes' => ['nullable', 'date_format:Y-m']]);
        $mes = $dados['mes'] ?? Carbon::now('America/Sao_Paulo')->format('Y-m');
        [$y, $m] = explode('-', $mes);

        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();

        $rows = DB::table('vendas as v')
            ->join('users as u', function ($join) {
                $join->on('u.id', '=', 'v.user_id')->on('u.empresa_id', '=', 'v.empresa_id')->where('u.is_platform_admin', false);
            })
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

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function getCommissioningBySellerPdf(Request $request)
    {
        $empresaId = $this->activeEmpresaId();
        $userId = Auth::id();

        $dados = $request->validate(['mes' => ['nullable', 'date_format:Y-m']]);
        $mes = $dados['mes'] ?? Carbon::now('America/Sao_Paulo')->format('Y-m');
        [$y, $m] = explode('-', $mes);

        $inicio = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->startOfMonth()->toDateString();
        $fim = Carbon::createFromDate((int) $y, (int) $m, 1, 'America/Sao_Paulo')->endOfMonth()->toDateString();

        $rows = DB::table('vendas as v')
            ->join('users as u', function ($join) {
                $join->on('u.id', '=', 'v.user_id')->on('u.empresa_id', '=', 'v.empresa_id')->where('u.is_platform_admin', false);
            })
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
            $impostoPerc = isset($r->imposto) ? (float) $r->imposto : 0.0;

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
            'imposto' => optional($rows->first())->imposto ?? 0,
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
        $this->garantirGestaoComissionamento();
        $user = Auth::user();

        // Validação + normalizações simples
        $data = $request->validate([
            'vendedor_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('empresa_id', $this->tenantId())->where('is_platform_admin', false))],
            'mes' => ['required', 'regex:/^\d{4}-\d{2}$/'], // YYYY-MM
            'natureza' => ['required', 'in:DEBITO,CREDITO'],
            'categoria' => ['required', 'in:MOTIVACIONAL,AJUSTE,DESCONTO,OUTRO,ANGARIACAO,PRESTACAO'],
            'imposto_perc' => ['required', 'numeric', 'min:0', 'max:100'],
            'valor_bruto' => ['required', 'numeric', 'min:0.01'],
            'descricao' => ['nullable', 'string', 'max:255'],
            // Parcelamento
            'parcelado' => ['nullable', 'boolean'],
            'parcelas' => ['nullable', 'integer', 'min:1', 'max:60', function ($attribute, $value, $fail) use ($request) {
                // Valida min:2 apenas se parcelado estiver marcado
                if ($request->input('parcelado') && $value < 2) {
                    $fail('O número de parcelas deve ser no mínimo 2 quando parcelado.');
                }
            }],
            // Os campos abaixo vêm do front, mas serão ignorados p/ segurança:
            'imposto_valor' => ['nullable'],
            'valor_liquido' => ['nullable'],
        ]);

        // (opcional) garantir que vendedor pertence à mesma empresa do usuário logado
        // abort_if(!User::where('id',$data['vendedor_id'])->where('empresa_id',$this->tenantId())->exists(), 403);

        $parcelado = (bool) ($data['parcelado'] ?? false);
        $parcelas = (int) ($data['parcelas'] ?? 1);

        if ($parcelado && $parcelas > 1) {
            // ========== LANÇAMENTO PARCELADO ==========
            $valorTotal = (float) $data['valor_bruto'];
            $valorParcela = round($valorTotal / $parcelas, 2);

            // Ajuste da última parcela para compensar arredondamento
            $totalParcelas = $valorParcela * $parcelas;
            $diferenca = round($valorTotal - $totalParcelas, 2);

            $mesBase = Carbon::createFromFormat('Y-m', $data['mes']);
            $lancamentoPrincipal = null;
            $lancamentos = [];

            DB::transaction(function () use (
                $user, $data, $parcelas, $valorTotal, $valorParcela, $diferenca, $mesBase,
                &$lancamentoPrincipal, &$lancamentos
            ) {
                for ($i = 1; $i <= $parcelas; $i++) {
                    // Primeira parcela (1/3) no mês ATUAL (mês base = hoje), demais nos meses seguintes
                    // Exemplo: hoje = outubro/2025, 3 parcelas de R$ 100
                    //   Parcela 1/3 → 2025-10 (outubro)  [addMonths(0)]
                    //   Parcela 2/3 → 2025-11 (novembro) [addMonths(1)]
                    //   Parcela 3/3 → 2025-12 (dezembro) [addMonths(2)]
                    $mesAtual = $mesBase->copy()->addMonths($i - 1)->format('Y-m');

                    // Última parcela recebe ajuste para compensar arredondamento
                    $valorAtual = ($i === $parcelas) ? ($valorParcela + $diferenca) : $valorParcela;

                    $lanc = LancamentoDebitoCredito::create([
                        'empresa_id' => $this->tenantId(),
                        'vendedor_id' => (int) $data['vendedor_id'],
                        'created_by' => $user->id,
                        'natureza' => $data['natureza'],
                        'categoria' => $data['categoria'],
                        'valor_bruto' => $valorAtual,
                        'imposto_perc' => (float) $data['imposto_perc'],
                        'mes' => $mesAtual,
                        'descricao' => $data['descricao'] ?? null,
                        'status' => LancamentoDebitoCredito::ST_PENDENTE,
                        'parcelado' => true,
                        'parcela_atual' => $i,
                        'parcelas_total' => $parcelas,
                        'valor_total_original' => $valorTotal,
                        'lancamento_principal_id' => $lancamentoPrincipal?->id,
                    ]);

                    if ($i === 1) {
                        $lancamentoPrincipal = $lanc;
                    }

                    $lancamentos[] = $lanc;
                }
            });

            return response()->json([
                'message' => "Lançamento parcelado criado com sucesso ({$parcelas} parcelas).",
                'lancamentos' => $lancamentos,
                'total_parcelas' => $parcelas,
            ], 201);

        } else {
            // ========== LANÇAMENTO À VISTA ==========
            $lanc = LancamentoDebitoCredito::create([
                'empresa_id' => $this->tenantId(),
                'vendedor_id' => (int) $data['vendedor_id'],
                'created_by' => $user->id,
                'natureza' => $data['natureza'],
                'categoria' => $data['categoria'],
                'valor_bruto' => (float) $data['valor_bruto'],
                'imposto_perc' => (float) $data['imposto_perc'],
                'mes' => $data['mes'],
                'descricao' => $data['descricao'] ?? null,
                'status' => LancamentoDebitoCredito::ST_PENDENTE,
                'parcelado' => false,
            ]);

            return response()->json([
                'message' => 'Ajuste lançado com sucesso.',
                'lancamento' => $lanc,
            ], 201);
        }
    }

    public function deleteLancamentoDebitoCredito($id)
    {
        $this->garantirGestaoComissionamento();
        $user = Auth::user();

        // Buscar lançamento
        $lancamento = LancamentoDebitoCredito::where('id', $id)
            ->where('empresa_id', $this->tenantId())
            ->first();

        if (! $lancamento) {
            return response()->json([
                'success' => false,
                'message' => 'Lançamento não encontrado.',
            ], 404);
        }

        // Verificar se já foi pago
        if ($lancamento->status === LancamentoDebitoCredito::ST_PAGO) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir um lançamento que já foi pago.',
            ], 422);
        }

        // Excluir
        $lancamento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lançamento excluído com sucesso.',
        ]);
    }

    public function pagarVendedor(Request $request)
    {
        $this->garantirGestaoComissionamento();
        $empresaId = $this->activeEmpresaId();
        $vendedorIdInformado = (int) $request->input('vendedor_id');
        $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
            'vendedor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('is_platform_admin', false)
                    ->whereIn('user_role_id', [UserRole::VENDEDOR, UserRole::BACKOFFICE])),
            ],
            'data_pagamento' => ['required', 'date_format:Y-m-d'],
            // NOVO: Recebe objetos com venda_id + tipo_item
            'itens' => ['array', function (string $attribute, mixed $value, \Closure $fail): void {
                $chaves = collect($value)->map(fn ($item) => ($item['venda_id'] ?? '').':'.($item['tipo_item'] ?? ''));

                if ($chaves->duplicates()->isNotEmpty()) {
                    $fail('A mesma venda e o mesmo tipo não podem ser enviados mais de uma vez.');
                }
            }],
            'itens.*.venda_id' => [
                'required',
                'integer',
                Rule::exists('vendas', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('user_id', $vendedorIdInformado)),
            ],
            'itens.*.tipo_item' => ['required', 'in:COMISSAO,ANGARIACAO'],
            // Mantém retrocompatibilidade com venda_ids (será convertido para itens)
            'venda_ids' => ['array'],
            'venda_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('vendas', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('user_id', $vendedorIdInformado)),
            ],
            'ajuste_ids' => ['array'],
            'ajuste_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('lancamentos_debito_credito', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('vendedor_id', $vendedorIdInformado)
                    ->where('status', LancamentoDebitoCredito::ST_PENDENTE)),
            ],
        ]);

        $adminUserId = Auth::id();
        $vendedorId = (int) $request->integer('vendedor_id');
        $mes = $request->input('mes'); // Mantido para compatibilidade com o registro
        $dataPagto = Carbon::parse($request->input('data_pagamento'))->toDateString();

        // Processa itens: novo formato (itens) ou retrocompatível (venda_ids)
        $itens = collect($request->input('itens', []));
        if ($itens->isEmpty() && $request->has('venda_ids')) {
            // Retrocompatibilidade: converte venda_ids para formato de itens (assume COMISSAO)
            $itens = collect($request->input('venda_ids', []))->map(fn ($id) => [
                'venda_id' => (int) $id,
                'tipo_item' => 'COMISSAO',
            ]);
        }

        // Separa itens por tipo
        $comissaoIds = $itens->where('tipo_item', 'COMISSAO')->pluck('venda_id')->map('intval')->toArray();
        $angariacaoIds = $itens->where('tipo_item', 'ANGARIACAO')->pluck('venda_id')->map('intval')->toArray();
        $ajusteIds = array_map('intval', $request->input('ajuste_ids', []));

        /* ================== VENDAS - COMISSÃO ================== */
        $rowsComissao = collect();
        if (! empty($comissaoIds)) {
            $rowsComissao = DB::table('vendas as v')
                ->join('users as u', function ($join) {
                    $join->on('u.id', '=', 'v.user_id')->on('u.empresa_id', '=', 'v.empresa_id')->where('u.is_platform_admin', false);
                })
                ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
                    $j->on('cfg.user_id', '=', 'v.user_id')->where('cfg.empresa_id', '=', $empresaId);
                })
                ->where('v.empresa_id', $empresaId)
                ->where('v.user_id', $vendedorId)
                ->whereNotNull('v.data_implantacao')
                ->where('v.comissao_paga', 0)
                ->whereIn('v.id', $comissaoIds)
                ->select([
                    'v.id',
                    'v.user_id',
                    'u.name as vendedor',
                    DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
                    'v.data_implantacao',
                    DB::raw('LOWER(cfg.grade) as grade'),
                    'cfg.imposto',
                    'cfg.percentual',
                    'cfg.percentual_angariacao',
                    'v.angariacao_status',
                    DB::raw('COALESCE(v.angariacao_valor,0) as angariacao_valor'),
                ])
                ->orderBy('v.data_implantacao')
                ->get();
        }

        /* ================== VENDAS - ANGARIAÇÃO ================== */
        $rowsAngariacao = collect();
        if (! empty($angariacaoIds)) {
            $rowsAngariacao = DB::table('vendas as v')
                ->join('users as u', function ($join) {
                    $join->on('u.id', '=', 'v.user_id')->on('u.empresa_id', '=', 'v.empresa_id')->where('u.is_platform_admin', false);
                })
                ->join('comissionamento_configuracao as cfg', function ($j) use ($empresaId) {
                    $j->on('cfg.user_id', '=', 'v.user_id')->where('cfg.empresa_id', '=', $empresaId);
                })
                ->where('v.empresa_id', $empresaId)
                ->where('v.user_id', $vendedorId)
                ->whereNotNull('v.data_implantacao')
                ->whereRaw("UPPER(v.angariacao_status) = 'SIM'")
                ->where('v.angariacao_paga', 0)
                ->whereIn('v.id', $angariacaoIds)
                ->select([
                    'v.id',
                    'v.user_id',
                    'u.name as vendedor',
                    DB::raw('COALESCE(v.valor_contrato,0) as valor_contrato'),
                    'v.data_implantacao',
                    DB::raw('LOWER(cfg.grade) as grade'),
                    'cfg.imposto',
                    'cfg.percentual',
                    'cfg.percentual_angariacao',
                    'v.angariacao_status',
                    DB::raw('COALESCE(v.angariacao_valor,0) as angariacao_valor'),
                ])
                ->orderBy('v.data_implantacao')
                ->get();
        }

        if ($rowsComissao->count() !== count($comissaoIds)
            || $rowsAngariacao->count() !== count($angariacaoIds)) {
            throw ValidationException::withMessages([
                'itens' => 'Uma ou mais vendas não estão disponíveis para pagamento nesta empresa.',
            ]);
        }

        // Enriquece itens de COMISSÃO
        $enrichedComissao = $rowsComissao->map(function ($r) {
            $base = (float) $r->valor_contrato;
            $perc = isset($r->percentual) ? (float) $r->percentual : 0.0;
            $impP = isset($r->imposto) ? (float) $r->imposto : 0.0;

            $bruto = round($base * ($perc / 100), 2);
            $impVal = round($bruto * ($impP / 100), 2);
            $liquido = round($bruto - $impVal, 2);

            $r->tipo_lancamento = 'COMISSAO';
            $r->percentual = $perc;
            $r->imposto_perc = $impP;
            $r->bruto = $bruto;
            $r->imposto_valor = $impVal;
            $r->liquido = $liquido;
            $r->valor_base_para_item = $base;

            return $r;
        });

        // Enriquece itens de ANGARIAÇÃO
        $enrichedAngariacao = $rowsAngariacao->map(function ($r) {
            $percentualAngariacao = (float) $r->percentual_angariacao;
            $base = (float) $r->angariacao_valor;
            $perc = $percentualAngariacao;
            $impP = 0.0; // Sem imposto para angariação

            $bruto = round($base * ($perc / 100), 2);
            $impVal = 0.0;
            $liquido = $bruto;

            $r->tipo_lancamento = 'ANGARIACAO';
            $r->percentual = $perc;
            $r->imposto_perc = $impP;
            $r->bruto = $bruto;
            $r->imposto_valor = $impVal;
            $r->liquido = $liquido;
            $r->valor_base_para_item = $base;

            return $r;
        });

        // Combina ambos para cálculo de totais
        $enriched = $enrichedComissao->merge($enrichedAngariacao);

        /* ================== AJUSTES ================== */
        $ajustes = collect();
        if (! empty($ajusteIds)) {
            $ajustes = DB::table('lancamentos_debito_credito as a')
                ->where('a.empresa_id', $empresaId)
                ->where('a.vendedor_id', $vendedorId)
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

        if ($ajustes->count() !== count($ajusteIds)) {
            throw ValidationException::withMessages([
                'ajuste_ids' => 'Um ou mais ajustes não estão disponíveis para pagamento nesta empresa.',
            ]);
        }

        if ($enriched->isEmpty() && $ajustes->isEmpty()) {
            return response()->json(['message' => 'Nenhum item selecionado para pagamento.'], 422);
        }

        /* ================== TOTAIS ================== */
        $totVendas = [
            'bruto' => (float) $enriched->sum('bruto'),
            'imposto' => (float) $enriched->sum('imposto_valor'),
            'liquido' => (float) $enriched->sum('liquido'),
        ];

        $totAjustes = [
            'bruto' => (float) $ajustes->sum('valor_bruto'),
            'imposto' => (float) $ajustes->sum('imposto_valor'),
            'liquido' => (float) $ajustes->sum('valor_liquido'), // já com sinal
        ];

        $totais = [
            'bruto' => round($totVendas['bruto'] + $totAjustes['bruto'], 2),
            'imposto' => round($totVendas['imposto'] + $totAjustes['imposto'], 2),
            'liquido' => round($totVendas['liquido'] + $totAjustes['liquido'], 2),
        ];

        // Snapshot de perfil (para header): se não houver vendas, usa 0
        $cfg = $rowsComissao->first() ?? $rowsAngariacao->first();
        $salario = 0.0; // <<< SALÁRIO FORA DO CÁLCULO
        $percCom = (float) ($cfg->percentual ?? 0);
        $percImp = (float) ($cfg->imposto ?? 0);
        $totalRec = round($totais['liquido'], 2); // <<< TOTAL A RECEBER = APENAS LÍQUIDO

        $vendedor = ($rowsComissao->first()->vendedor ?? $rowsAngariacao->first()->vendedor ?? null)
            ?? DB::table('users')->where('empresa_id', $empresaId)->where('is_platform_admin', false)->where('id', $vendedorId)->value('name')
            ?? 'Vendedor';

        /* ================== TRANSAÇÃO ================== */
        $pagamentoId = DB::transaction(function () use (
            $empresaId, $vendedorId, $adminUserId, $mes, $dataPagto,
            $enriched, $enrichedComissao, $enrichedAngariacao, $ajustes, $totais, $totalRec, $percCom, $percImp
        ) {
            $idsVendas = $enriched->pluck('id')->map('intval')->unique()->sort()->values();
            if ($idsVendas->isNotEmpty()) {
                $vendasBloqueadas = DB::table('vendas')
                    ->where('empresa_id', $empresaId)
                    ->where('user_id', $vendedorId)
                    ->whereIn('id', $idsVendas)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'comissao_paga', 'angariacao_paga']);

                if ($vendasBloqueadas->count() !== $idsVendas->count()) {
                    throw ValidationException::withMessages([
                        'itens' => 'Uma ou mais vendas deixaram de estar disponíveis para pagamento.',
                    ]);
                }

                $comissaoIdsSelecionados = $enrichedComissao->pluck('id')->map('intval');
                $angariacaoIdsSelecionados = $enrichedAngariacao->pluck('id')->map('intval');
                $indisponivel = $vendasBloqueadas->contains(fn ($venda) => ($comissaoIdsSelecionados->contains((int) $venda->id) && (bool) $venda->comissao_paga)
                    || ($angariacaoIdsSelecionados->contains((int) $venda->id) && (bool) $venda->angariacao_paga)
                );

                if ($indisponivel) {
                    throw ValidationException::withMessages([
                        'itens' => 'Uma ou mais vendas já foram pagas em outro lançamento.',
                    ]);
                }
            }

            if ($ajustes->isNotEmpty()) {
                $ajustesBloqueados = DB::table('lancamentos_debito_credito')
                    ->where('empresa_id', $empresaId)
                    ->where('vendedor_id', $vendedorId)
                    ->whereIn('id', $ajustes->pluck('id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'status']);

                if ($ajustesBloqueados->count() !== $ajustes->count()
                    || $ajustesBloqueados->contains(fn ($ajuste) => $ajuste->status !== LancamentoDebitoCredito::ST_PENDENTE)) {
                    throw ValidationException::withMessages([
                        'ajuste_ids' => 'Um ou mais ajustes já foram pagos ou deixaram de estar disponíveis.',
                    ]);
                }
            }

            // SEMPRE CRIA um novo pagamento (header)
            $headerId = DB::table('comissao_pagamentos')->insertGetId([
                'empresa_id' => $empresaId,
                'vendedor_id' => $vendedorId,
                'mes' => $mes,
                'data_pagamento' => $dataPagto,
                'percentual_comissao' => $percCom,
                'percentual_imposto' => $percImp,
                'total_bruto' => $totais['bruto'],
                'total_imposto' => $totais['imposto'],
                'total_liquido' => $totais['liquido'],
                'salario' => 0,
                'total_receber' => $totalRec,
                'created_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Itens de VENDAS (COMISSÃO + ANGARIAÇÃO separados)
            if ($enriched->isNotEmpty()) {
                foreach ($enriched as $r) {
                    // Verifica se já existe item para esta venda COM MESMO TIPO
                    $itemExistente = DB::table('comissao_pagamento_itens as item')
                        ->join('comissao_pagamentos as pagamento', 'pagamento.id', '=', 'item.comissao_pagamento_id')
                        ->where('pagamento.empresa_id', $empresaId)
                        ->where('item.venda_id', $r->id)
                        ->where('item.tipo_lancamento', $r->tipo_lancamento)
                        ->select('item.*')
                        ->first();

                    $dados = [
                        'comissao_pagamento_id' => $headerId,
                        'venda_id' => $r->id,
                        'ajuste_id' => null,
                        'is_adicional' => 0,
                        'tipo_lancamento' => $r->tipo_lancamento, // COMISSAO ou ANGARIACAO
                        'descricao' => null,
                        'valor_contrato' => $r->valor_base_para_item,
                        'percentual' => $r->percentual,
                        'imposto_perc' => $r->imposto_perc,
                        'bruto' => $r->bruto,
                        'imposto_valor' => $r->imposto_valor,
                        'liquido' => $r->liquido,
                        'updated_at' => now(),
                    ];

                    if ($itemExistente) {
                        // Atualiza o item existente
                        DB::table('comissao_pagamento_itens')
                            ->where('id', $itemExistente->id)
                            ->update($dados);
                    } else {
                        // Cria novo item
                        $dados['created_at'] = now();
                        DB::table('comissao_pagamento_itens')->insert($dados);
                    }
                }

                // Marca COMISSÃO como paga
                if ($enrichedComissao->isNotEmpty()) {
                    DB::table('vendas')
                        ->where('empresa_id', $empresaId)
                        ->whereIn('id', $enrichedComissao->pluck('id'))
                        ->update([
                            'comissao_paga' => 1,
                            'data_pagamento_comissao' => now(),
                            'updated_at' => now(),
                        ]);
                }

                // Marca ANGARIAÇÃO como paga
                if ($enrichedAngariacao->isNotEmpty()) {
                    DB::table('vendas')
                        ->where('empresa_id', $empresaId)
                        ->whereIn('id', $enrichedAngariacao->pluck('id'))
                        ->update([
                            'angariacao_paga' => 1,
                            'data_pagamento_angariacao' => now(),
                            'updated_at' => now(),
                        ]);
                }
            }

            // Itens de AJUSTES
            if ($ajustes->isNotEmpty()) {
                foreach ($ajustes as $a) {
                    // Verifica se já existe item para este ajuste
                    $itemExistente = DB::table('comissao_pagamento_itens as item')
                        ->join('comissao_pagamentos as pagamento', 'pagamento.id', '=', 'item.comissao_pagamento_id')
                        ->where('pagamento.empresa_id', $empresaId)
                        ->where('item.ajuste_id', $a->id)
                        ->select('item.*')
                        ->first();

                    $tipo = in_array($a->categoria, ['MOTIVACIONAL', 'AJUSTE', 'BONUS', 'OUTRO', 'ANGARIACAO', 'PRESTACAO']) ? $a->categoria : 'AJUSTE';

                    $dados = [
                        'comissao_pagamento_id' => $headerId,
                        'venda_id' => null,
                        'ajuste_id' => $a->id,
                        'is_adicional' => 1,
                        'tipo_lancamento' => $tipo,
                        'descricao' => $a->descricao,
                        'valor_contrato' => (float) $a->valor_bruto,
                        'percentual' => 0,
                        'imposto_perc' => (float) $a->imposto_perc,
                        'bruto' => (float) $a->valor_bruto,
                        'imposto_valor' => (float) $a->imposto_valor,
                        'liquido' => (float) $a->valor_liquido,
                        'updated_at' => now(),
                    ];

                    if ($itemExistente) {
                        // Atualiza o item existente
                        DB::table('comissao_pagamento_itens')
                            ->where('id', $itemExistente->id)
                            ->update($dados);
                    } else {
                        // Cria novo item
                        $dados['created_at'] = now();
                        DB::table('comissao_pagamento_itens')->insert($dados);
                    }
                }

                DB::table('lancamentos_debito_credito')
                    ->where('empresa_id', $empresaId)
                    ->whereIn('id', $ajustes->pluck('id'))
                    ->update([
                        'status' => 'pago',
                        'comissao_pagamento_id' => $headerId,
                        'pago_em' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return $headerId;
        });

        return response()->json([
            'message' => "Pagamento registrado para {$vendedor}.",
            'pagamento_id' => $pagamentoId,
            'pdf_url' => route('comissionamento.pagamento.pdf', $pagamentoId),
            'totais' => $totais,
            'salario' => 0.0,
            'percentual_comissao' => $percCom,
            'percentual_imposto' => $percImp,
            'total_receber' => $totalRec,
        ]);
    }

    public function pdfPagamento($pagamentoId)
    {
        $empresaId = $this->activeEmpresaId();

        $p = DB::table('comissao_pagamentos as p')
            ->join('users as u', function ($join) {
                $join->on('u.id', '=', 'p.vendedor_id')->on('u.empresa_id', '=', 'p.empresa_id')->where('u.is_platform_admin', false);
            })
            ->where('p.empresa_id', $empresaId)
            ->where('p.id', $pagamentoId)
            ->select('p.*', 'u.name as vendedor')
            ->first();

        abort_if(! $p, 404);
        abort_unless(
            (int) $p->vendedor_id === (int) Auth::id()
                || Auth::user()->isPlatformAdmin()
                || in_array((int) Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true),
            403
        );

        $gradeAtual = DB::table('comissionamento_configuracao')
            ->where('empresa_id', $empresaId)
            ->where('user_id', $p->vendedor_id)
            ->select(DB::raw('LOWER(grade) as grade'))
            ->value('grade');

        // Itens (Vendas + Ajustes)
        $itens = DB::table('comissao_pagamento_itens as i')
            ->leftJoin('vendas as v', function ($join) use ($empresaId) {
                $join->on('v.id', '=', 'i.venda_id')->where('v.empresa_id', '=', $empresaId);
            })
            ->leftJoin('lancamentos_debito_credito as a', function ($join) use ($empresaId) {
                $join->on('a.id', '=', 'i.ajuste_id')->where('a.empresa_id', '=', $empresaId);
            })
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
            $isAjuste = ! is_null($i->ajuste_id) || is_null($i->venda_id);

            if ($isAjuste) {
                return (object) [
                    'is_ajuste' => true,
                    'tipo_label' => strtoupper($i->ajuste_natureza ?? '') === 'DEBITO' ? 'Ajuste (Débito)' : 'Ajuste (Crédito)',
                    'tipo_categoria' => $i->ajuste_categoria,
                    'descricao' => $i->ajuste_descricao,
                    'data_implantacao' => null,
                    'nome_contrato' => sprintf(
                        '%s · %s%s',
                        strtoupper($i->ajuste_natureza ?? 'AJUSTE'),
                        strtoupper($i->ajuste_categoria ?? 'OUTRO'),
                        $i->ajuste_descricao ? (' — '.$i->ajuste_descricao) : ''
                    ),
                    'operadora' => '-',
                    'valor_contrato' => (float) $i->valor_contrato,
                    'percentual' => null,
                    'bruto' => (float) $i->bruto,
                    'imposto_valor' => (float) $i->imposto_valor,
                    'liquido' => (float) $i->liquido, // pode ser negativo
                    'angariacao_valor' => 0,
                    'angariacao_status' => null,
                ];
            }

            // Venda ou Angariação (baseado no tipo_lancamento do item)
            $isAngariacao = strtoupper((string) $i->tipo_lancamento) === 'ANGARIACAO';

            return (object) [
                'is_ajuste' => false,
                'tipo_label' => $isAngariacao ? 'Angariação' : 'Venda',
                'tipo_lancamento' => $i->tipo_lancamento,
                'tipo_categoria' => null,
                'descricao' => null,
                'data_implantacao' => $i->data_implantacao,
                'nome_contrato' => $i->nome_contrato,
                'operadora' => $i->operadora,
                'valor_contrato' => (float) $i->valor_contrato, // base usada no cálculo
                'percentual' => (float) $i->percentual,
                'bruto' => (float) $i->bruto,
                'imposto_valor' => (float) $i->imposto_valor,
                'liquido' => (float) $i->liquido,
                'angariacao_valor' => (float) $i->angariacao_valor,
                'angariacao_status' => $i->angariacao_status,
            ];
        });

        // Totais do header
        $totais = [
            'bruto' => (float) $p->total_bruto,
            'imposto' => (float) $p->total_imposto,
            'liquido' => (float) $p->total_liquido,
        ];

        // Quebra Vendas vs Ajustes
        $linVendas = $linhas->where('is_ajuste', false);
        $linAjustes = $linhas->where('is_ajuste', true);

        $totVendas = [
            'bruto' => (float) $linVendas->sum('bruto'),
            'imposto' => (float) $linVendas->sum('imposto_valor'),
            'liquido' => (float) $linVendas->sum('liquido'),
        ];

        $totAjustes = [
            'bruto' => (float) $linAjustes->sum('bruto'),
            'imposto' => (float) $linAjustes->sum('imposto_valor'),
            'liquido' => (float) $linAjustes->sum('liquido'), // com sinal
            'creditos' => (float) $linAjustes->filter(fn ($r) => $r->liquido > 0)->sum('liquido'),
            'debitos' => (float) abs($linAjustes->filter(fn ($r) => $r->liquido < 0)->sum('liquido')),
        ];

        // Perfil para exibição (salário fora do PDF)
        $perfil = [
            'grade' => $gradeAtual,
            'percentual' => (float) $p->percentual_comissao,
            'salario' => 0.0, // não exibir salário
            'imposto' => (float) $p->percentual_imposto,
        ];

        // Período
        $periodo = Carbon::createFromFormat('Y-m-d', "{$p->mes}-01")
            ->locale('pt_BR')
            ->isoFormat('MMMM [de] YYYY');

        // >>> Data de pagamento formatada (flag)
        $pagoEmFmt = $p->pago_em ? Carbon::parse($p->pago_em)->format('d/m/Y') : null;

        // >>> Conta de pagamento (do registro ou padrão do vendedor)
        $conta = null;
        if (! empty($p->conta_pagamento_id)) {
            $conta = DB::table('contas_pagamento')
                ->where('id', $p->conta_pagamento_id)
                ->where('user_id', $p->vendedor_id)
                ->first();
        }
        if (! $conta) {
            $conta = DB::table('contas_pagamento')
                ->where('user_id', $p->vendedor_id)
                ->where('is_default', 1)
                ->first();
        }

        $agenciaConta = $conta
            ? trim(($conta->agencia ?? '').' / '.($conta->conta ?? '').(! empty($conta->digito) ? '-'.$conta->digito : ''))
            : null;
        $chavePix = $conta->chave_pix ?? null;

        $empresa = Empresa::findOrFail($empresaId);
        $pdf = Pdf::loadView('pdf.comissionamento-vendedor', [
            'mes' => $p->mes,
            'periodo' => mb_convert_case($periodo, MB_CASE_TITLE, 'UTF-8'),
            'vendedor' => $p->vendedor,
            'linhas' => $linhas,
            'totais' => $totais,
            'totVendas' => $totVendas,
            'totAjustes' => $totAjustes,
            'perfil' => $perfil,
            'totalReceber' => (float) $p->total_liquido, // somente líquido

            // >>> novos dados para a view
            'pagoEm' => $pagoEmFmt,     // "DD/MM/AAAA" ou null
            'agenciaConta' => $agenciaConta,  // string ou null
            'chavePix' => $chavePix,      // string ou null
            'empresaNome' => $empresa->nome_fantasia,
            'empresaCnpj' => $empresa->cpf_cnpj,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("pagamento_comissao_{$p->mes}_{$p->vendedor}.pdf");
    }

    public function pagamentosIndex()
    {
        $empresaId = $this->activeEmpresaId();

        // Apenas ADMINISTRATIVO e DEVELOPER podem ver todos os lançamentos
        $podeVerTodos = $this->podeGerenciarComissionamento();

        if ($podeVerTodos) {
            $vendedores = DB::table('users as u')
                ->join('comissao_pagamentos as p', function ($join) {
                    $join->on('p.vendedor_id', '=', 'u.id')->on('p.empresa_id', '=', 'u.empresa_id');
                })
                ->where('p.empresa_id', $empresaId)
                ->select('u.id', 'u.name')
                ->distinct()
                ->orderBy('u.name')
                ->get();
        } else {
            // VENDEDOR, BACKOFFICE, SUPERVISOR só veem os próprios lançamentos
            $vendedores = DB::table('users as u')
                ->join('comissao_pagamentos as p', function ($join) {
                    $join->on('p.vendedor_id', '=', 'u.id')->on('p.empresa_id', '=', 'u.empresa_id');
                })
                ->where('p.empresa_id', $empresaId)
                ->where('u.id', Auth::user()->id)
                ->select('u.id', 'u.name')
                ->distinct()
                ->orderBy('u.name')
                ->get();
        }

        // quem criou (admins)
        $criadores = DB::table('users as u')
            ->join('comissao_pagamentos as p', function ($join) {
                $join->on('p.created_by', '=', 'u.id');
            })
            ->where('p.empresa_id', $empresaId)
            ->where(function ($query) use ($empresaId) {
                $query->where('u.empresa_id', $empresaId)
                    ->orWhere('u.is_platform_admin', true);
            })
            ->select('u.id', 'u.name')
            ->distinct()
            ->orderBy('u.name')
            ->get();

        $podeGerenciarPagamentos = $this->podeGerenciarComissionamento();

        return view('content.pages.comissionamento.pagamentos-index', compact(
            'vendedores',
            'criadores',
            'podeGerenciarPagamentos',
        ));
    }

    public function pagamentosData(Request $request)
    {
        $user = auth()->user();
        $empresaId = (int) $this->tenantId();

        // Apenas ADMINISTRATIVO e DEVELOPER podem ver todos os lançamentos
        $podeVerTodos = $this->podeGerenciarComissionamento();

        $dados = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('is_platform_admin', false)),
            ],
            'created_by' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->orWhere('is_platform_admin', true)),
            ],
            'status' => ['nullable', Rule::in(['pago', 'pendente', 'todos'])],
        ]);

        $mes = $dados['mes'] ?? null;
        $vendedorId = (int) ($dados['vendedor_id'] ?? 0);
        $criadorId = (int) ($dados['created_by'] ?? 0);
        $status = $dados['status'] ?? null;

        $q = DB::table('comissao_pagamentos as p')
            ->join('users as v', function ($join) {
                $join->on('v.id', '=', 'p.vendedor_id')->on('v.empresa_id', '=', 'p.empresa_id')->where('v.is_platform_admin', false);
            })
            ->join('users as c', function ($join) {
                $join->on('c.id', '=', 'p.created_by');
            })
            ->where('p.empresa_id', $empresaId)
            ->where(function ($query) use ($empresaId) {
                $query->where('c.empresa_id', $empresaId)
                    ->orWhere('c.is_platform_admin', true);
            })
            ->whereNotNull('p.data_pagamento'); // Filtra apenas pagamentos com data definida

        if ($podeVerTodos) {
            // ADMINISTRATIVO e DEVELOPER podem filtrar por qualquer vendedor/criador
            if (! empty($vendedorId)) {
                $q->where('p.vendedor_id', $vendedorId);
            }
            if (! empty($criadorId)) {
                $q->where('p.created_by', $criadorId);
            }
        } else {
            // VENDEDOR, BACKOFFICE, SUPERVISOR só veem os próprios lançamentos
            $q->where('p.vendedor_id', $user->id);
        }

        if (! empty($mes)) {
            // Filtra pelo mês da data_pagamento (quando será/foi pago), não pelo campo 'mes' (referência)
            // Exemplo: mes='2025-11' → busca data_pagamento BETWEEN '2025-11-01' AND '2025-11-30'
            $mesInicio = $mes.'-01';
            $mesFim = date('Y-m-t', strtotime($mesInicio)); // último dia do mês
            $q->whereBetween('p.data_pagamento', [$mesInicio, $mesFim]);
        }

        // Filtro de status: pago (pago_em preenchido) ou pendente (pago_em null)
        if ($status === 'pago') {
            $q->whereNotNull('p.pago_em');
        } elseif ($status === 'pendente') {
            $q->whereNull('p.pago_em');
        }
        // Se status vazio ou 'todos', não filtra (mostra ambos)

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
        $this->garantirGestaoComissionamento();
        $empresaId = $this->activeEmpresaId();

        // carrega header
        $header = DB::table('comissao_pagamentos')
            ->where('empresa_id', $empresaId)
            ->where('id', $id)
            ->first();

        if (! $header) {
            return response()->json(['message' => 'Pagamento não encontrado.'], 404);
        }

        DB::transaction(function () use ($id, $empresaId) {
            // 1. Busca os itens relacionados com seus tipos
            $itens = DB::table('comissao_pagamento_itens')
                ->where('comissao_pagamento_id', $id)
                ->whereNotNull('venda_id')
                ->select('venda_id', 'tipo_lancamento')
                ->get();

            // Separa por tipo
            $comissaoIds = $itens->where('tipo_lancamento', 'COMISSAO')->pluck('venda_id')->filter()->unique()->toArray();
            $angariacaoIds = $itens->where('tipo_lancamento', 'ANGARIACAO')->pluck('venda_id')->filter()->unique()->toArray();

            // 2. Atualiza as vendas para remover a marcação de COMISSÃO paga
            if (! empty($comissaoIds)) {
                DB::table('vendas')->where('empresa_id', $empresaId)->whereIn('id', $comissaoIds)->update([
                    'comissao_paga' => 0,
                    'data_pagamento_comissao' => null,
                    'updated_at' => now(),
                ]);
            }

            // 3. Atualiza as vendas para remover a marcação de ANGARIAÇÃO paga
            if (! empty($angariacaoIds)) {
                DB::table('vendas')->where('empresa_id', $empresaId)->whereIn('id', $angariacaoIds)->update([
                    'angariacao_paga' => 0,
                    'data_pagamento_angariacao' => null,
                    'updated_at' => now(),
                ]);
            }

            // 3. Volta ajustes para status pendente (FK com SET NULL, então apenas marca pendente)
            DB::table('lancamentos_debito_credito')
                ->where('empresa_id', $empresaId)
                ->where('comissao_pagamento_id', $id)
                ->update([
                    'status' => 'pendente',
                    'comissao_pagamento_id' => null,
                    'pago_em' => null,
                    'updated_at' => now(),
                ]);

            // 4. DELETE dos itens (CASCADE automático, mas fazemos explícito para clareza)
            DB::table('comissao_pagamento_itens')
                ->where('comissao_pagamento_id', $id)
                ->delete();

            // 5. DELETE do header (registro principal)
            DB::table('comissao_pagamentos')
                ->where('empresa_id', $empresaId)
                ->where('id', $id)
                ->delete();
        });

        return response()->json(['message' => 'Pagamento estornado e registro excluído com sucesso.']);
    }

    public function byUser(Request $request)
    {
        $request->validate(['user_id' => ['required', 'integer']]);

        $userId = (int) $request->query('user_id');
        User::query()->tenantMember($this->activeEmpresaId())->findOrFail($userId);
        abort_unless(
            $userId === (int) Auth::id()
                || Auth::user()->isPlatformAdmin()
                || in_array((int) Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true),
            403
        );

        $contas = ContaPagamento::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->get(['id', 'banco', 'agencia', 'conta', 'digito', 'chave_pix', 'descricao', 'is_default']);

        return response()->json($contas);
    }

    public function pagar(Request $request, int $id)
    {
        $this->garantirGestaoComissionamento();
        $pag = ComissaoPagamento::where('empresa_id', $this->activeEmpresaId())->findOrFail($id);

        $validated = $request->validate([
            'pago_em' => ['nullable', 'date'],
            'conta_pagamento_id' => [
                'nullable',
                Rule::exists('contas_pagamento', 'id')->where('user_id', $pag->vendedor_id),
            ],
        ]);

        $pagoEm = Carbon::parse($validated['pago_em'] ?? now('America/Sao_Paulo'))->toDateString();
        $contaId = $validated['conta_pagamento_id'] ?? null;

        if (! $contaId) {
            $contaId = ContaPagamento::where('user_id', $pag->vendedor_id)->where('is_default', true)->value('id');
        }

        $pag->update([
            'pago_em' => $pagoEm,
            'conta_pagamento_id' => $contaId,
        ]);

        return response()->json([
            'ok' => true,
            'pago_em' => $pag->pago_em,
            'conta_pagamento_id' => $pag->conta_pagamento_id,
        ]);
    }

    private function garantirGestaoComissionamento(): void
    {
        abort_unless(
            $this->podeGerenciarComissionamento(),
            403,
            'Acesso não autorizado.'
        );
    }

    private function podeGerenciarComissionamento(): bool
    {
        return Auth::user()->isPlatformAdmin()
            || in_array((int) Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER], true);
    }

    private function activeEmpresaId(): int
    {
        return app(TenantContext::class)->id();
    }
}
