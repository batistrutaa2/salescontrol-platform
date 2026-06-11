<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\Tabulations;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PosVendaDemandaTemplate;
use App\Models\User;
use App\Models\VendaDemanda;
use App\Models\Vendas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Workspace de pós-venda: um card por contrato implantado com demandas pendentes.
 *
 * Regra de pertencimento à tela: contrato é IMPLANTADO e tem >= 1 demanda PENDENTE.
 * "Concluir todas" zera as pendentes -> o card cai naturalmente.
 *
 * As ações por demanda (toggle/editar/criar/remover) reutilizam os endpoints
 * `*DemandaContrato` já existentes em Backoffice.
 */
class PosVendaDemandas extends Controller
{
    public function index()
    {
        $empresaId = Auth::user()->empresa_id;
        $isAdmin = in_array(Auth::user()->user_role_id, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER]);

        $operadoras = DB::table('vendas')
            ->where('empresa_id', $empresaId)
            ->whereNotNull('operadora')
            ->where('operadora', '!=', '')
            ->distinct()
            ->orderBy('operadora')
            ->pluck('operadora');

        // Lista de responsáveis (backoffice) só faz sentido para admin filtrar.
        $backoffices = $isAdmin
            ? User::select('id', 'name')
                ->where('empresa_id', $empresaId)
                ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER, UserRole::BACKOFFICE])
                ->where('ativo', 'Y')
                ->orderBy('name')
                ->get()
            : collect();

        return view('content.pages.backoffice.pos-venda-demandas', [
            'operadoras' => $operadoras,
            'backoffices' => $backoffices,
            'isAdmin' => $isAdmin,
            'tipoLabels' => TipoDemandaContrato::labels(),
        ]);
    }

    public function data(Request $request)
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $isBackoffice = Auth::user()->user_role_id == UserRole::BACKOFFICE;

            $busca = $request->input('busca');
            $operadora = $request->input('operadora');
            $backofficeId = $request->input('backoffice_id');

            // 1) IDs de contratos implantados visíveis (aplicando filtros + papel).
            $query = DB::table('vendas')
                ->select('vendas.id')
                ->where('vendas.empresa_id', $empresaId)
                ->where('vendas.tabulacao_id', Tabulations::IMPLANTADO);

            if ($busca) {
                $query->where(function ($q) use ($busca) {
                    $q->where('vendas.nome_contrato', 'like', "%{$busca}%")
                        ->orWhere('vendas.numero_proposta', 'like', "%{$busca}%")
                        ->orWhere('vendas.cpf_cnpj', 'like', "%{$busca}%")
                        ->orWhere('vendas.operadora', 'like', "%{$busca}%");
                });
            }
            if ($operadora) {
                $query->where('vendas.operadora', $operadora);
            }

            // BACKOFFICE só enxerga o que implantou (ou contratos sem dono).
            if ($isBackoffice) {
                $query->where(function ($q) {
                    $q->where('vendas.backoffice_id', Auth::id())
                        ->orWhereNull('vendas.backoffice_id');
                });
            } elseif ($backofficeId) {
                // Admin pode filtrar por responsável.
                if ($backofficeId === 'sem') {
                    $query->whereNull('vendas.backoffice_id');
                } else {
                    $query->where('vendas.backoffice_id', $backofficeId);
                }
            }

            $vendaIds = $query->pluck('vendas.id')->toArray();

            if (empty($vendaIds)) {
                return response()->json(['success' => true, 'contratos' => [], 'kpis' => $this->kpisVazios()]);
            }

            // 2) Contratos que ainda têm pelo menos 1 demanda PENDENTE.
            $vendasComPendente = VendaDemanda::where('empresa_id', $empresaId)
                ->where('status', 'PENDENTE')
                ->whereIn('venda_id', $vendaIds)
                ->distinct()
                ->pluck('venda_id')
                ->toArray();

            if (empty($vendasComPendente)) {
                return response()->json(['success' => true, 'contratos' => [], 'kpis' => $this->kpisVazios()]);
            }

            // 3) Info dos contratos.
            $vendaInfo = DB::table('vendas')
                ->select(
                    'vendas.id',
                    'vendas.nome_contrato',
                    'vendas.numero_proposta',
                    'vendas.cpf_cnpj',
                    'vendas.operadora',
                    'vendas.nome_plano',
                    'vendas.data_implantacao',
                    'vendas.backoffice_id',
                    'bo.name as backoffice_nome'
                )
                ->leftJoin('users as bo', 'bo.id', '=', 'vendas.backoffice_id')
                ->whereIn('vendas.id', $vendasComPendente)
                ->get()
                ->keyBy('id');

            // 4) Todas as demandas desses contratos (pendentes + concluídas).
            $demandasPorVenda = VendaDemanda::with(['criador:id,name', 'concluidaPor:id,name'])
                ->where('empresa_id', $empresaId)
                ->whereIn('venda_id', $vendasComPendente)
                ->orderByRaw("FIELD(status, 'PENDENTE', 'CONCLUIDA')")
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('venda_id');

            $contratos = collect($vendasComPendente)
                ->map(function ($vendaId) use ($vendaInfo, $demandasPorVenda) {
                    $info = $vendaInfo[$vendaId] ?? null;
                    if (! $info) {
                        return null;
                    }

                    $demandas = $demandasPorVenda[$vendaId] ?? collect();
                    $total = $demandas->count();
                    $pendentes = $demandas->where('status', 'PENDENTE')->count();
                    $concluidas = $total - $pendentes;

                    $dataImplantacao = $info->data_implantacao
                        ? Carbon::parse($info->data_implantacao)
                        : null;

                    return [
                        'venda_id' => $vendaId,
                        'nome_contrato' => $info->nome_contrato,
                        'numero_proposta' => $info->numero_proposta,
                        'cpf_cnpj' => $info->cpf_cnpj,
                        'operadora' => $info->operadora,
                        'nome_plano' => $info->nome_plano,
                        'backoffice_nome' => $info->backoffice_nome,
                        'data_implantacao' => $dataImplantacao?->format('d/m/Y'),
                        'dias_implantado' => $dataImplantacao ? (int) $dataImplantacao->diffInDays(now()) : null,
                        'total' => $total,
                        'pendentes' => $pendentes,
                        'concluidas' => $concluidas,
                        'progresso' => $total > 0 ? (int) round(($concluidas / $total) * 100) : 0,
                        'demandas' => $demandas->values(),
                    ];
                })
                ->filter()
                // Mais pendências primeiro, depois implantação mais antiga.
                ->sortByDesc('pendentes')
                ->values();

            return response()->json([
                'success' => true,
                'contratos' => $contratos,
                'kpis' => [
                    'contratos_pendentes' => $contratos->count(),
                    'demandas_pendentes' => $contratos->sum('pendentes'),
                    'demandas_concluidas_hoje' => VendaDemanda::where('empresa_id', $empresaId)
                        ->where('status', 'CONCLUIDA')
                        ->whereIn('venda_id', $vendasComPendente)
                        ->whereDate('concluida_em', today())
                        ->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar demandas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Conclui todas as demandas PENDENTE do contrato de uma vez.
     */
    public function concluirTodas(int $vendaId)
    {
        try {
            $empresaId = Auth::user()->empresa_id;

            $venda = Vendas::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->first();

            if (! $venda) {
                return response()->json(['success' => false, 'message' => 'Contrato não encontrado.'], 404);
            }

            if (! $this->canEditContract($venda)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para concluir as demandas deste contrato.',
                ], 403);
            }

            $afetadas = VendaDemanda::where('empresa_id', $empresaId)
                ->where('venda_id', $vendaId)
                ->where('status', 'PENDENTE')
                ->update([
                    'status' => 'CONCLUIDA',
                    'concluida_por' => Auth::id(),
                    'concluida_em' => now(),
                    'updated_at' => now(),
                ]);

            $venda->update([
                'pos_venda_concluida_em' => now(),
                'pos_venda_concluida_por' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pós-venda concluída! '.$afetadas.' demanda(s) finalizada(s).',
                'afetadas' => $afetadas,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao concluir demandas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca contratos IMPLANTADOS (mesmo os sem demanda pendente) para abrir um
     * chamado avulso — ex.: reembolso de um cliente implantado há tempo.
     * Respeita o mesmo filtro por papel da listagem principal.
     */
    public function buscarContratos(Request $request)
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $isBackoffice = Auth::user()->user_role_id == UserRole::BACKOFFICE;
            $termo = trim((string) $request->input('termo', ''));

            $query = DB::table('vendas')
                ->select(
                    'vendas.id',
                    'vendas.nome_contrato',
                    'vendas.numero_proposta',
                    'vendas.cpf_cnpj',
                    'vendas.operadora',
                    'vendas.data_implantacao',
                    'bo.name as backoffice_nome'
                )
                ->leftJoin('users as bo', 'bo.id', '=', 'vendas.backoffice_id')
                ->where('vendas.empresa_id', $empresaId)
                ->where('vendas.tabulacao_id', Tabulations::IMPLANTADO);

            if ($termo !== '') {
                $query->where(function ($q) use ($termo) {
                    $q->where('vendas.nome_contrato', 'like', "%{$termo}%")
                        ->orWhere('vendas.numero_proposta', 'like', "%{$termo}%")
                        ->orWhere('vendas.cpf_cnpj', 'like', "%{$termo}%")
                        ->orWhere('vendas.operadora', 'like', "%{$termo}%");
                });
            }

            if ($isBackoffice) {
                $query->where(function ($q) {
                    $q->where('vendas.backoffice_id', Auth::id())
                        ->orWhereNull('vendas.backoffice_id');
                });
            }

            $contratos = $query->orderByDesc('vendas.data_implantacao')
                ->limit(25)
                ->get()
                ->map(fn ($v) => [
                    'venda_id' => $v->id,
                    'nome_contrato' => $v->nome_contrato,
                    'numero_proposta' => $v->numero_proposta,
                    'cpf_cnpj' => $v->cpf_cnpj,
                    'operadora' => $v->operadora,
                    'backoffice_nome' => $v->backoffice_nome,
                    'data_implantacao' => $v->data_implantacao
                        ? Carbon::parse($v->data_implantacao)->format('d/m/Y')
                        : null,
                ]);

            return response()->json(['success' => true, 'contratos' => $contratos]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar contratos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Relatório de pós-venda do mês: demandas concluídas, tempo médio de conclusão,
     * contratos finalizados e recortes por tipo / por responsável.
     * BACKOFFICE vê apenas o que implantou; ADMINISTRATIVO/DEVELOPER veem tudo
     * (com filtro opcional por responsável).
     */
    public function metricas(Request $request)
    {
        try {
            $empresaId = Auth::user()->empresa_id;
            $isBackoffice = Auth::user()->user_role_id == UserRole::BACKOFFICE;
            $isAdmin = ! $isBackoffice;
            $backofficeFiltro = $request->input('backoffice_id');

            // Mês de referência (YYYY-MM); default = mês atual.
            try {
                $ref = $request->filled('mes')
                    ? Carbon::createFromFormat('Y-m', $request->input('mes'))->startOfMonth()
                    : now()->startOfMonth();
            } catch (\Throwable $e) {
                $ref = now()->startOfMonth();
            }
            $inicio = $ref->copy()->startOfMonth();
            $fim = $ref->copy()->endOfMonth();

            // Base: demandas da empresa + contrato (para o dono/backoffice).
            $aplicarPapel = function ($q) use ($isBackoffice, $backofficeFiltro) {
                if ($isBackoffice) {
                    $q->where('v.backoffice_id', Auth::id());
                } elseif ($backofficeFiltro === 'sem') {
                    $q->whereNull('v.backoffice_id');
                } elseif ($backofficeFiltro) {
                    $q->where('v.backoffice_id', $backofficeFiltro);
                }

                return $q;
            };

            $base = fn () => $aplicarPapel(
                DB::table('venda_demandas as d')
                    ->join('vendas as v', 'v.id', '=', 'd.venda_id')
                    ->where('d.empresa_id', $empresaId)
            );

            // Concluídas no mês + tempo médio (created_at -> concluida_em).
            $concluidasMes = $base()
                ->where('d.status', 'CONCLUIDA')
                ->whereBetween('d.concluida_em', [$inicio, $fim])
                ->count();

            $tempoMedioMin = $base()
                ->where('d.status', 'CONCLUIDA')
                ->whereBetween('d.concluida_em', [$inicio, $fim])
                ->whereNotNull('d.concluida_em')
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, d.created_at, d.concluida_em)'));

            // Pendentes atuais (não dependem do mês).
            $pendentes = $base()->where('d.status', 'PENDENTE')->count();

            // Contratos com pós-venda finalizada no mês.
            $contratosFinalizadosQ = DB::table('vendas as v')
                ->where('v.empresa_id', $empresaId)
                ->whereBetween('v.pos_venda_concluida_em', [$inicio, $fim]);
            $aplicarPapel($contratosFinalizadosQ);
            $contratosFinalizados = $contratosFinalizadosQ->count();

            // Concluídas por tipo (top 6) no mês.
            $porTipo = $base()
                ->where('d.status', 'CONCLUIDA')
                ->whereBetween('d.concluida_em', [$inicio, $fim])
                ->groupBy('d.tipo')
                ->select('d.tipo', DB::raw('COUNT(*) as total'))
                ->orderByDesc('total')
                ->limit(6)
                ->get()
                ->map(fn ($r) => [
                    'tipo' => $r->tipo,
                    'label' => TipoDemandaContrato::tryFrom($r->tipo)?->label() ?? $r->tipo,
                    'total' => (int) $r->total,
                ]);

            // Por responsável (somente admin).
            $porBackoffice = collect();
            if ($isAdmin) {
                $porBackoffice = $base()
                    ->where('d.status', 'CONCLUIDA')
                    ->whereBetween('d.concluida_em', [$inicio, $fim])
                    ->leftJoin('users as bo', 'bo.id', '=', 'v.backoffice_id')
                    ->groupBy('v.backoffice_id', 'bo.name')
                    ->select(
                        'bo.name as nome',
                        DB::raw('COUNT(*) as concluidas'),
                        DB::raw('AVG(TIMESTAMPDIFF(MINUTE, d.created_at, d.concluida_em)) as tempo_min')
                    )
                    ->orderByDesc('concluidas')
                    ->get()
                    ->map(fn ($r) => [
                        'nome' => $r->nome ?? 'Sem responsável',
                        'concluidas' => (int) $r->concluidas,
                        'tempo_medio' => $this->formatarDuracao($r->tempo_min !== null ? (float) $r->tempo_min : null),
                    ]);
            }

            return response()->json([
                'success' => true,
                'is_admin' => $isAdmin,
                'mes_label' => $this->mesLabel($ref),
                'metricas' => [
                    'concluidas_mes' => $concluidasMes,
                    'tempo_medio' => $this->formatarDuracao($tempoMedioMin !== null ? (float) $tempoMedioMin : null),
                    'contratos_finalizados' => $contratosFinalizados,
                    'pendentes' => $pendentes,
                ],
                'por_tipo' => $porTipo,
                'por_backoffice' => $porBackoffice,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar métricas: '.$e->getMessage(),
            ], 500);
        }
    }

    private function formatarDuracao(?float $minutos): string
    {
        if ($minutos === null) {
            return '—';
        }
        $min = (int) round($minutos);
        if ($min < 60) {
            return $min.' min';
        }
        $horas = intdiv($min, 60);
        if ($horas < 24) {
            $resto = $min % 60;

            return $horas.'h'.($resto ? ' '.$resto.'min' : '');
        }
        $dias = intdiv($horas, 24);
        $restoH = $horas % 24;

        return $dias.'d'.($restoH ? ' '.$restoH.'h' : '');
    }

    private function mesLabel(Carbon $ref): string
    {
        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        return $meses[$ref->month - 1].'/'.$ref->year;
    }

    /**
     * Templates ativos da empresa, para o dropdown de "Adicionar demanda".
     */
    public function getTemplates()
    {
        $empresaId = Auth::user()->empresa_id;
        PosVendaDemandaTemplate::seedDefaults($empresaId);

        $templates = PosVendaDemandaTemplate::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->get(['tipo', 'titulo', 'descricao']);

        return response()->json(['success' => true, 'templates' => $templates]);
    }

    private function kpisVazios(): array
    {
        return [
            'contratos_pendentes' => 0,
            'demandas_pendentes' => 0,
            'demandas_concluidas_hoje' => 0,
        ];
    }

    /**
     * Mesma regra de Backoffice::canEditContract: ADM/DEV editam tudo; BACKOFFICE
     * só o que implantou; demais somente leitura.
     */
    private function canEditContract(Vendas $sale): bool
    {
        $roleId = Auth::user()->user_role_id;

        if (in_array($roleId, [UserRole::ADMINISTRATIVO, UserRole::DEVELOPER])) {
            return true;
        }

        if ($roleId === UserRole::BACKOFFICE) {
            if ($sale->backoffice_id === null) {
                return false;
            }

            return $sale->backoffice_id === Auth::id();
        }

        return false;
    }
}
