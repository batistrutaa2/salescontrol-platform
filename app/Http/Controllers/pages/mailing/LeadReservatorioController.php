<?php

namespace App\Http\Controllers\pages\mailing;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\LeadReservatorioEstrategia;
use App\Models\LeadReservatorioExecucao;
use App\Models\LeadReservatorioItem;
use App\Models\User;
use App\Services\LeadReservatorioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadReservatorioController extends Controller
{
    public function __construct(private readonly LeadReservatorioService $service) {}

    public function index(Request $request): View
    {
        $this->garantirAcesso($request);
        $empresaId = (int) $request->user()->empresa_id;
        $this->service->sincronizarBloqueados($empresaId);

        return view('content.pages.mailing.reservatorio', [
            'vendedores' => User::query()
                ->where('empresa_id', $empresaId)
                ->where('user_role_id', UserRole::VENDEDOR)
                ->orderByDesc('ativo')
                ->orderBy('name')
                ->get(['id', 'name', 'ativo']),
            'migracaoConcluida' => LeadReservatorioExecucao::query()
                ->where('chave_idempotencia', "MIGRACAO_INICIAL:{$empresaId}")
                ->exists(),
        ]);
    }

    public function dados(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $empresaId = (int) $request->user()->empresa_id;
        $this->service->sincronizarBloqueados($empresaId);
        $query = $this->service->queryDisponiveis($empresaId)
            ->select([
                'l.id', 'l.contato_id', 'l.origem', 'l.status', 'l.entrou_em',
                'c.nome_cliente', 'c.cpf', 'c.telefone1', 'c.email', 'c.nome_base',
                'c.plano', 'c.categoria', 'c.entidade', 'c.idades', 'c.vidas',
                'c.valor_plano_atual', 'c.valor_negociacao', 'c.tipo_layout',
                'c.tipo_criativo', 'c.is_ads', 'c.plano_ativo', 'c.possui_cnpj',
            ]);

        if ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));
            $query->where(function ($q) use ($busca) {
                $q->where('c.nome_cliente', 'like', "%{$busca}%")
                    ->orWhere('c.cpf', 'like', "%{$busca}%")
                    ->orWhere('c.telefone1', 'like', "%{$busca}%")
                    ->orWhere('c.nome_base', 'like', "%{$busca}%");
            });
        }
        if ($request->filled('origem')) {
            $query->where('l.origem', $request->input('origem'));
        }
        if ($request->filled('base')) {
            $query->where('c.nome_base', $request->input('base'));
        }

        $itens = $query->orderByDesc('l.entrou_em')->paginate(25);
        $itens->getCollection()->transform(function ($item) {
            $item->entrou_em = optional(\Carbon\Carbon::parse($item->entrou_em))->timezone('America/Sao_Paulo')->format('d/m/Y H:i');

            return $item;
        });

        $metricas = [
            'disponiveis' => $this->service->queryDisponiveis($empresaId)->count(),
            'entradas_30_dias' => DB::table('lead_reservatorio_itens')->where('empresa_id', $empresaId)->where('entrou_em', '>=', now()->subDays(30))->count(),
            'distribuidos_mes' => DB::table('lead_reservatorio_itens')->where('empresa_id', $empresaId)->where('status', LeadReservatorioItem::STATUS_DISTRIBUIDO)->where('distribuido_em', '>=', now()->startOfMonth())->count(),
            'bloqueados' => DB::table('lead_reservatorio_itens')->where('empresa_id', $empresaId)->where('status', LeadReservatorioItem::STATUS_BLOQUEADO)->count(),
        ];

        $bases = DB::table('lead_reservatorio_itens as l')
            ->join('contatos as c', 'c.id', '=', 'l.contato_id')
            ->where('l.empresa_id', $empresaId)
            ->whereNotNull('c.nome_base')
            ->where('c.nome_base', '!=', '')
            ->distinct()->orderBy('c.nome_base')->pluck('c.nome_base');

        return response()->json(['itens' => $itens, 'metricas' => $metricas, 'bases' => $bases]);
    }

    public function estrategias(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $estrategias = LeadReservatorioEstrategia::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->orderByDesc('ativo')->orderBy('nome')->get();

        return response()->json(['data' => $estrategias]);
    }

    public function storeEstrategia(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'condicoes' => ['required', 'array', 'min:1', 'max:15'],
            'condicoes.*.campo' => ['required', 'string'],
            'condicoes.*.operador' => ['required', 'string'],
            'condicoes.*.valor' => ['nullable'],
        ]);
        $this->service->preview((int) $request->user()->empresa_id, $validated['condicoes']);
        $estrategia = LeadReservatorioEstrategia::create([
            'empresa_id' => $request->user()->empresa_id,
            'nome' => $validated['nome'],
            'condicoes' => $validated['condicoes'],
            'ativo' => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Estratégia salva com sucesso.', 'data' => $estrategia], 201);
    }

    public function updateEstrategia(Request $request, int $estrategia): JsonResponse
    {
        $this->garantirAcesso($request);
        $registro = $this->estrategiaDaEmpresa($request, $estrategia);
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'condicoes' => ['required', 'array', 'min:1', 'max:15'],
            'condicoes.*.campo' => ['required', 'string'],
            'condicoes.*.operador' => ['required', 'string'],
            'condicoes.*.valor' => ['nullable'],
            'ativo' => ['sometimes', 'boolean'],
        ]);
        $this->service->preview((int) $request->user()->empresa_id, $validated['condicoes']);
        $registro->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Estratégia atualizada.', 'data' => $registro->fresh()]);
    }

    public function arquivarEstrategia(Request $request, int $estrategia): JsonResponse
    {
        $this->garantirAcesso($request);
        $registro = $this->estrategiaDaEmpresa($request, $estrategia);
        $registro->update(['ativo' => false, 'updated_by' => $request->user()->id]);

        return response()->json(['message' => 'Estratégia arquivada.']);
    }

    public function previewEstrategia(Request $request, int $estrategia): JsonResponse
    {
        $this->garantirAcesso($request);
        $registro = $this->estrategiaDaEmpresa($request, $estrategia);

        return response()->json($this->service->preview((int) $request->user()->empresa_id, $registro->condicoes));
    }

    public function previewCondicoes(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $validated = $request->validate([
            'condicoes' => ['required', 'array', 'min:1', 'max:15'],
            'condicoes.*.campo' => ['required', 'string'],
            'condicoes.*.operador' => ['required', 'string'],
            'condicoes.*.valor' => ['nullable'],
        ]);

        return response()->json($this->service->preview(
            (int) $request->user()->empresa_id,
            $validated['condicoes'],
        ));
    }

    public function executarEstrategia(Request $request, int $estrategia): JsonResponse
    {
        $this->garantirAcesso($request);
        $validated = $request->validate([
            'distribuicoes' => ['required', 'array', 'min:1'],
            'distribuicoes.*.vendedor_id' => ['required', 'integer'],
            'distribuicoes.*.quantidade' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
        $execucao = $this->service->executar(
            (int) $request->user()->empresa_id,
            $estrategia,
            $validated['distribuicoes'],
            (int) $request->user()->id,
        );

        return response()->json([
            'message' => "{$execucao->total_executado} lead(s) distribuído(s) com sucesso.",
            'data' => $execucao,
        ]);
    }

    public function previewAleatoria(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);

        return response()->json($this->service->previewAleatoria(
            (int) $request->user()->empresa_id,
        ));
    }

    public function executarAleatoria(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $validated = $request->validate([
            'distribuicoes' => ['required', 'array', 'min:1'],
            'distribuicoes.*.vendedor_id' => ['required', 'integer'],
            'distribuicoes.*.quantidade' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
        $execucao = $this->service->executarAleatoria(
            (int) $request->user()->empresa_id,
            $validated['distribuicoes'],
            (int) $request->user()->id,
        );

        return response()->json([
            'message' => "{$execucao->total_executado} lead(s) sorteado(s) e distribuído(s) com sucesso.",
            'data' => $execucao,
        ]);
    }

    public function historico(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $empresaId = (int) $request->user()->empresa_id;
        $execucoes = DB::table('lead_reservatorio_execucoes as e')
            ->leftJoin('lead_reservatorio_estrategias as s', 's.id', '=', 'e.estrategia_id')
            ->leftJoin('users as autor', 'autor.id', '=', 'e.created_by')
            ->leftJoin('users as origem', 'origem.id', '=', 'e.vendedor_origem_id')
            ->where('e.empresa_id', $empresaId)
            ->select('e.*', 's.nome as estrategia_nome', 'autor.name as autor_nome', 'origem.name as vendedor_origem_nome')
            ->orderByDesc('e.id')->paginate(20);

        return response()->json(['data' => $execucoes]);
    }

    public function previewMigracao(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $validated = $request->validate(['vendedor_id' => ['required', 'integer']]);

        return response()->json($this->service->previewMigracao(
            (int) $request->user()->empresa_id,
            (int) $validated['vendedor_id'],
        ));
    }

    public function migrar(Request $request): JsonResponse
    {
        $this->garantirAcesso($request);
        $validated = $request->validate(['vendedor_id' => ['required', 'integer']]);
        $execucao = $this->service->migrarCarteiraInicial(
            (int) $request->user()->empresa_id,
            (int) $validated['vendedor_id'],
            (int) $request->user()->id,
        );

        return response()->json([
            'message' => "{$execucao->total_executado} lead(s) movido(s) para o reservatório.",
            'data' => $execucao,
        ]);
    }

    private function garantirAcesso(Request $request): void
    {
        abort_unless(in_array((int) $request->user()->user_role_id, [
            UserRole::DEVELOPER,
            UserRole::ADMINISTRATIVO,
            UserRole::SUPERVISOR,
        ], true), 403);
    }

    private function estrategiaDaEmpresa(Request $request, int $id): LeadReservatorioEstrategia
    {
        return LeadReservatorioEstrategia::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->findOrFail($id);
    }
}
