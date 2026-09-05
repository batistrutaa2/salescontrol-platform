<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CredencialAcesso;
use App\Models\CredencialAcessoHistorico;
use App\Models\Operadora;
use App\Models\Vendas;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class CredenciaisAcessoController extends Controller
{
    private const ALLOWED_ROLES = [
        UserRole::ADMINISTRATIVO,
        UserRole::BACKOFFICE,
        UserRole::DEVELOPER,
        UserRole::SUPERVISOR,
    ];

    /** Campos comparados na geração de histórico de edição. */
    private const CAMPOS_AUDITAVEIS = [
        'operadora_id' => 'Operadora',
        'tipo' => 'Tipo',
        'nome' => 'Nome',
        'login' => 'Login',
        'senha' => 'Senha',
        'observacao' => 'Observação',
        'status' => 'Status',
    ];

    private function checkAccess(): void
    {
        if (! in_array(Auth::user()->user_role_id, self::ALLOWED_ROLES)) {
            abort(403, 'Acesso não autorizado.');
        }
    }

    private function empresaId(): int
    {
        return app(TenantContext::class)->id();
    }

    public function index()
    {
        $this->checkAccess();

        $operadoras = Operadora::where('empresa_id', $this->empresaId())
            ->where('status', 'Y')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $base = CredencialAcesso::where('empresa_id', $this->empresaId());

        $resumo = [
            'total' => (clone $base)->count(),
            'ativos' => (clone $base)->where('status', 'Y')->count(),
            'inativos' => (clone $base)->where('status', 'N')->count(),
            'operadoras' => (clone $base)->whereNotNull('operadora_id')->distinct('operadora_id')->count('operadora_id'),
        ];

        return view('content.pages.backoffice.credenciais.index', compact('operadoras', 'resumo'));
    }

    public function getData(Request $request): JsonResponse
    {
        $this->checkAccess();

        $query = CredencialAcesso::with(['operadora', 'atualizadoPor'])
            ->where('credenciais_acesso.empresa_id', $this->empresaId());

        if ($request->filled('operadora_id')) {
            $query->where('operadora_id', $request->operadora_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('operadora_nome', fn (CredencialAcesso $row) => $row->operadora?->nome ?? '—')
            ->addColumn('atualizado_por_nome', fn (CredencialAcesso $row) => $row->atualizadoPor?->name ?? '—')
            // O accessor getUpdatedAtAttribute já devolve a data formatada (d/m/Y H:i:s)
            // em America/Sao_Paulo — não reprocessar com Carbon::parse.
            ->addColumn('atualizado_em', fn (CredencialAcesso $row) => $row->updated_at ?? '—')
            ->editColumn('status', fn (CredencialAcesso $row) => $row->status)
            ->filterColumn('nome', function ($q, $keyword) {
                $q->where('credenciais_acesso.nome', 'like', "%{$keyword}%")
                    ->orWhere('credenciais_acesso.login', 'like', "%{$keyword}%")
                    ->orWhere('credenciais_acesso.observacao', 'like', "%{$keyword}%");
            })
            ->make(true);
    }

    /** Pesquisa compacta do cofre, usada dentro da aba Cliente do contrato. */
    public function pesquisar(Request $request): JsonResponse
    {
        $this->checkAccess();

        $termo = trim((string) $request->input('q', ''));
        if (mb_strlen($termo) < 2) {
            return response()->json(['success' => true, 'acessos' => []]);
        }

        $acessos = CredencialAcesso::with('operadora:id,nome')
            ->where('empresa_id', $this->empresaId())
            ->where(function ($query) use ($termo) {
                $like = "%{$termo}%";
                $query->where('nome', 'like', $like)
                    ->orWhere('login', 'like', $like)
                    ->orWhere('cnpj', 'like', $like)
                    ->orWhere('tipo', 'like', $like)
                    ->orWhere('observacao', 'like', $like)
                    ->orWhereHas('operadora', fn ($operadora) => $operadora->where('nome', 'like', $like));
            })
            ->orderBy('nome')
            ->limit(20)
            ->get()
            ->map(fn (CredencialAcesso $acesso) => [
                'id' => $acesso->id,
                'operadora_id' => $acesso->operadora_id,
                'operadora' => $acesso->operadora?->nome,
                'tipo' => $acesso->tipo,
                'nome' => $acesso->nome,
                'login' => $acesso->login,
                'senha' => $acesso->senha,
                'observacao' => $acesso->observacao,
                'status' => $acesso->status,
            ])
            ->values();

        return response()->json(['success' => true, 'acessos' => $acessos]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'operadora_id' => ['nullable', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->empresaId())],
            'tipo' => 'nullable|string|max:50',
            'nome' => 'required|string|max:255',
            'login' => 'nullable|string|max:255',
            'senha' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
            'status' => 'required|in:Y,N',
        ]);

        $credencial = DB::transaction(function () use ($validated) {
            $userId = Auth::id();

            $credencial = CredencialAcesso::create(array_merge($validated, [
                'empresa_id' => $this->empresaId(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            CredencialAcessoHistorico::create([
                'empresa_id' => $this->empresaId(),
                'credencial_id' => $credencial->id,
                'user_id' => $userId,
                'acao' => 'CRIACAO',
                'created_at' => now(),
            ]);

            return $credencial;
        });

        return response()->json([
            'success' => true,
            'message' => 'Credencial cadastrada com sucesso!',
            'id' => $credencial->id,
        ], 201);
    }

    /**
     * Cadastra vários acessos (login/senha) de uma vez, compartilhando
     * operadora/tipo/status/observação. Cada item de "acessos" vira uma credencial.
     */
    public function storeMultiplo(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'operadora_id' => ['nullable', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->empresaId())],
            'tipo' => 'nullable|string|max:50',
            'observacao' => 'nullable|string',
            'status' => 'required|in:Y,N',
            'venda_id' => ['nullable', 'integer', Rule::exists('vendas', 'id')->where('empresa_id', $this->empresaId())],
            'acessos' => 'required|array|min:1',
            'acessos.*.nome' => 'required|string|max:255',
            'acessos.*.login' => 'nullable|string|max:255',
            'acessos.*.senha' => 'nullable|string|max:255',
        ]);

        // Quando o cadastro parte da tela de um contrato, amarra o acesso ao
        // contrato (venda_id) e ao CNPJ do cliente — assim ele já aparece na
        // aba "Acessos do cliente" (casada por venda_id/CNPJ).
        $vendaId = null;
        $cnpj = null;
        if (! empty($validated['venda_id'])) {
            $venda = Vendas::where('empresa_id', $this->empresaId())->find($validated['venda_id']);
            $vendaId = $venda?->id;
            $cnpj = $venda ? preg_replace('/\D/', '', (string) $venda->cpf_cnpj) : null;
        }

        $qtd = DB::transaction(function () use ($validated, $vendaId, $cnpj) {
            $userId = Auth::id();
            $criadas = 0;

            foreach ($validated['acessos'] as $acesso) {
                $credencial = CredencialAcesso::create([
                    'empresa_id' => $this->empresaId(),
                    'operadora_id' => $validated['operadora_id'] ?? null,
                    'tipo' => $validated['tipo'] ?? null,
                    'venda_id' => $vendaId,
                    'cnpj' => $cnpj ?: null,
                    'nome' => $acesso['nome'],
                    'login' => $acesso['login'] ?? null,
                    'senha' => $acesso['senha'] ?? null,
                    'observacao' => $validated['observacao'] ?? null,
                    'status' => $validated['status'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                CredencialAcessoHistorico::create([
                    'empresa_id' => $this->empresaId(),
                    'credencial_id' => $credencial->id,
                    'user_id' => $userId,
                    'acao' => 'CRIACAO',
                    'created_at' => now(),
                ]);

                $criadas++;
            }

            return $criadas;
        });

        return response()->json([
            'success' => true,
            'message' => $qtd > 1 ? "{$qtd} acessos cadastrados!" : 'Acesso cadastrado!',
            'quantidade' => $qtd,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $this->checkAccess();

        $credencial = CredencialAcesso::where('empresa_id', $this->empresaId())->findOrFail($id);

        return response()->json($credencial);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'operadora_id' => ['nullable', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->empresaId())],
            'tipo' => 'nullable|string|max:50',
            'nome' => 'required|string|max:255',
            'login' => 'nullable|string|max:255',
            'senha' => 'nullable|string|max:255',
            'observacao' => 'nullable|string',
            'status' => 'required|in:Y,N',
        ]);

        // Normaliza o nome para MAIÚSCULAS antes do diff, mantendo histórico e
        // valor gravado consistentes (o mutator do model também aplica isso).
        $validated['nome'] = mb_strtoupper(trim($validated['nome']), 'UTF-8');

        DB::transaction(function () use ($validated, $id) {
            $userId = Auth::id();

            $credencial = CredencialAcesso::where('empresa_id', $this->empresaId())
                ->lockForUpdate()
                ->findOrFail($id);

            foreach (self::CAMPOS_AUDITAVEIS as $campo => $label) {
                $anterior = $campo === 'senha' ? $credencial->senha : $credencial->getOriginal($campo);
                $novo = $validated[$campo] ?? null;

                if ((string) $anterior !== (string) $novo) {
                    CredencialAcessoHistorico::create([
                        'empresa_id' => $this->empresaId(),
                        'credencial_id' => $credencial->id,
                        'user_id' => $userId,
                        'acao' => 'EDICAO',
                        'campo' => $label,
                        'valor_anterior' => $campo === 'senha' ? null : $anterior,
                        'valor_novo' => $campo === 'senha' ? null : $novo,
                        'created_at' => now(),
                    ]);
                }
            }

            $credencial->update(array_merge($validated, ['updated_by' => $userId]));
        });

        return response()->json([
            'success' => true,
            'message' => 'Credencial atualizada com sucesso!',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->checkAccess();

        DB::transaction(function () use ($id) {
            $credencial = CredencialAcesso::where('empresa_id', $this->empresaId())->findOrFail($id);

            CredencialAcessoHistorico::create([
                'empresa_id' => $this->empresaId(),
                'credencial_id' => $credencial->id,
                'user_id' => Auth::id(),
                'acao' => 'EXCLUSAO',
                'campo' => 'Nome',
                'valor_anterior' => $credencial->nome,
                'created_at' => now(),
            ]);

            $credencial->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Credencial excluída com sucesso!',
        ]);
    }

    public function historico(int $id): JsonResponse
    {
        $this->checkAccess();
        $empresaId = $this->empresaId();

        $credencial = CredencialAcesso::where('empresa_id', $empresaId)->findOrFail($id);

        $historico = $credencial->historico()
            ->with(['usuario' => fn ($query) => $query->tenantActor($empresaId)])
            ->get()->map(function (CredencialAcessoHistorico $h) {
                return [
                    'acao' => $h->acao,
                    'campo' => $h->campo,
                    'valor_anterior' => $h->valor_anterior,
                    'valor_novo' => $h->valor_novo,
                    'usuario' => $h->usuario?->name ?? 'Sistema',
                    'data' => $h->created_at
                        ? Carbon::parse($h->getRawOriginal('created_at'))->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s')
                        : '—',
                ];
            });

        return response()->json([
            'credencial' => [
                'id' => $credencial->id,
                'nome' => $credencial->nome,
            ],
            'historico' => $historico,
        ]);
    }

    // ----------------------------------------------------------------
    // Importação por Excel (por operadora + mapeamento de colunas)
    // ----------------------------------------------------------------

    /** Campos do sistema que podem receber uma coluna da planilha. */
    private const CAMPOS_IMPORTAVEIS = [
        'tipo' => 'Tipo',
        'nome' => 'Nome',
        'login' => 'Login / Documento',
        'senha' => 'Senha',
        'observacao' => 'Observação',
    ];

    /** Palavras-chave para adivinhar o mapeamento a partir do cabeçalho. */
    private const PALPITES = [
        'nome' => ['empresa', 'nome', 'cliente', 'razao', 'razão', 'titular'],
        'login' => ['login', 'usuario', 'usuário', 'cpf', 'cnpj', 'documento', 'user'],
        'senha' => ['senha', 'password', 'pass'],
        'observacao' => ['obs', 'observ', 'acesso', 'dia', 'email', 'e-mail', 'nota'],
        'tipo' => ['tipo'],
    ];

    /**
     * Lê a planilha enviada e devolve as colunas + amostra para o usuário mapear.
     */
    public function importPreview(Request $request): JsonResponse
    {
        $this->checkAccess();

        $request->validate([
            'arquivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $temCabecalho = $request->boolean('tem_cabecalho', true);

        $sheets = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\RawSheetImport(), $request->file('arquivo'));
        $rows = $sheets[0] ?? [];

        if (empty($rows)) {
            return response()->json(['message' => 'A planilha está vazia.'], 422);
        }

        $primeira = $rows[0];
        $totalColunas = count($primeira);

        $colunas = [];
        for ($i = 0; $i < $totalColunas; $i++) {
            $cabecalho = $temCabecalho ? trim((string) ($primeira[$i] ?? '')) : '';
            $colunas[] = [
                'index' => $i,
                'letra' => $this->indiceParaLetra($i),
                'label' => $cabecalho !== '' ? $cabecalho : 'Coluna '.$this->indiceParaLetra($i),
            ];
        }

        $amostraRows = $temCabecalho ? array_slice($rows, 1, 5) : array_slice($rows, 0, 5);
        $amostra = array_map(function ($row) use ($totalColunas) {
            $linha = [];
            for ($i = 0; $i < $totalColunas; $i++) {
                $linha[] = trim((string) ($row[$i] ?? ''));
            }

            return $linha;
        }, $amostraRows);

        return response()->json([
            'colunas' => $colunas,
            'amostra' => $amostra,
            'campos' => self::CAMPOS_IMPORTAVEIS,
            'palpite' => $this->adivinharMapeamento($colunas),
            'total_linhas' => $temCabecalho ? count($rows) - 1 : count($rows),
        ]);
    }

    /**
     * Importa as credenciais de uma operadora a partir do Excel + mapeamento.
     * Política: sempre adiciona (cria registros novos).
     */
    public function import(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'operadora_id' => ['required', 'integer', Rule::exists('operadoras', 'id')->where('empresa_id', $this->empresaId())],
            'arquivo' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'mapping' => 'required|array',
            'mapping.nome' => 'required',
        ]);

        // Garante que a operadora pertence à empresa do usuário.
        $operadora = Operadora::where('empresa_id', $this->empresaId())->findOrFail($validated['operadora_id']);

        $temCabecalho = $request->boolean('tem_cabecalho', true);
        $mapping = $this->normalizarMapping($request->input('mapping'));

        $sheets = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\RawSheetImport(), $request->file('arquivo'));
        $rows = $sheets[0] ?? [];
        $rows = $temCabecalho ? array_slice($rows, 1) : $rows;

        if (empty($rows)) {
            return response()->json(['message' => 'A planilha não tem linhas para importar.'], 422);
        }

        $importados = 0;
        $pulados = 0;

        DB::transaction(function () use ($rows, $mapping, $operadora, &$importados, &$pulados) {
            $userId = Auth::id();

            foreach ($rows as $row) {
                $nome = $this->valorMapeado($row, $mapping['nome'] ?? null);
                if ($nome === null) {
                    $pulados++;

                    continue;
                }

                $credencial = CredencialAcesso::create([
                    'empresa_id' => $this->empresaId(),
                    'operadora_id' => $operadora->id,
                    'tipo' => $this->valorMapeado($row, $mapping['tipo'] ?? null),
                    'nome' => $nome,
                    'login' => $this->valorMapeado($row, $mapping['login'] ?? null),
                    'senha' => $this->valorMapeado($row, $mapping['senha'] ?? null),
                    'observacao' => $this->valorMapeado($row, $mapping['observacao'] ?? null),
                    'status' => 'Y',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                CredencialAcessoHistorico::create([
                    'empresa_id' => $this->empresaId(),
                    'credencial_id' => $credencial->id,
                    'user_id' => $userId,
                    'acao' => 'CRIACAO',
                    'created_at' => now(),
                ]);

                $importados++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Importação concluída: {$importados} credenciais adicionadas".($pulados ? " ({$pulados} linhas sem nome ignoradas)." : '.'),
            'importados' => $importados,
            'pulados' => $pulados,
        ]);
    }

    /** Mantém apenas os campos válidos e converte índices para int (ou null). */
    private function normalizarMapping(array $mapping): array
    {
        $out = [];
        foreach (array_keys(self::CAMPOS_IMPORTAVEIS) as $campo) {
            $valor = $mapping[$campo] ?? null;
            $out[$campo] = ($valor === null || $valor === '') ? null : (int) $valor;
        }

        return $out;
    }

    /** Lê a célula da coluna mapeada, limpa, ou null se vazia/sem mapeamento. */
    private function valorMapeado(array $row, ?int $idx): ?string
    {
        if ($idx === null) {
            return null;
        }
        $valor = trim((string) ($row[$idx] ?? ''));

        return $valor === '' ? null : $valor;
    }

    /** Sugere o mapeamento casando o cabeçalho com palavras-chave conhecidas. */
    private function adivinharMapeamento(array $colunas): array
    {
        $palpite = [];
        foreach (self::PALPITES as $campo => $chaves) {
            foreach ($colunas as $coluna) {
                $label = mb_strtolower($coluna['label']);
                foreach ($chaves as $chave) {
                    if (str_contains($label, $chave)) {
                        $palpite[$campo] = $coluna['index'];

                        continue 3;
                    }
                }
            }
        }

        return $palpite;
    }

    /** 0 => A, 1 => B, ... 26 => AA. */
    private function indiceParaLetra(int $idx): string
    {
        $letra = '';
        $idx++;
        while ($idx > 0) {
            $idx--;
            $letra = chr(65 + ($idx % 26)).$letra;
            $idx = intdiv($idx, 26);
        }

        return $letra;
    }
}
