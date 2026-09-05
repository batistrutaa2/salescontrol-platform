<?php

namespace App\Services;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\LeadReservatorioEstrategia;
use App\Models\LeadReservatorioExecucao;
use App\Models\LeadReservatorioExecucaoItem;
use App\Models\LeadReservatorioItem;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadReservatorioService
{
    private const CAMPOS = [
        'origem' => ['coluna' => 'l.origem', 'tipo' => 'lista'],
        'nome_base' => ['coluna' => 'c.nome_base', 'tipo' => 'texto'],
        'plano' => ['coluna' => 'c.plano', 'tipo' => 'texto'],
        'categoria' => ['coluna' => 'c.categoria', 'tipo' => 'texto'],
        'entidade' => ['coluna' => 'c.entidade', 'tipo' => 'texto'],
        'idades' => ['coluna' => 'c.idades', 'tipo' => 'texto'],
        'tipo_layout' => ['coluna' => 'c.tipo_layout', 'tipo' => 'texto'],
        'tipo_criativo' => ['coluna' => 'c.tipo_criativo', 'tipo' => 'texto'],
        'is_ads' => ['coluna' => 'c.is_ads', 'tipo' => 'booleano'],
        'plano_ativo' => ['coluna' => 'c.plano_ativo', 'tipo' => 'booleano'],
        'possui_cnpj' => ['coluna' => 'c.possui_cnpj', 'tipo' => 'booleano'],
        'vidas' => ['coluna' => 'c.vidas', 'tipo' => 'numero'],
        'valor_plano_atual' => ['coluna' => 'c.valor_plano_atual', 'tipo' => 'numero'],
        'valor_negociacao' => ['coluna' => 'c.valor_negociacao', 'tipo' => 'numero'],
        'entrou_em' => ['coluna' => 'l.entrou_em', 'tipo' => 'data'],
    ];

    public function __construct(private readonly TabulationCatalog $tabulations) {}

    public function adicionarNovo(Contatos $contato, string $origem, ?int $userId): LeadReservatorioItem
    {
        if (! in_array($origem, [
            LeadReservatorioItem::ORIGEM_IMPORTACAO,
            LeadReservatorioItem::ORIGEM_MARKETING,
            LeadReservatorioItem::ORIGEM_MIGRACAO,
        ], true)) {
            throw new \InvalidArgumentException('Origem inválida para o reservatório.');
        }

        $existente = LeadReservatorioItem::query()
            ->where('empresa_id', $contato->empresa_id)
            ->where('contato_id', $contato->id)
            ->first();

        if ($existente) {
            return $existente;
        }

        if (! $this->contatoPodeEntrar((int) $contato->empresa_id, (int) $contato->id)) {
            throw new \InvalidArgumentException('O contato não está elegível para entrar no reservatório.');
        }

        return LeadReservatorioItem::create([
            'empresa_id' => $contato->empresa_id,
            'contato_id' => $contato->id,
            'origem' => $origem,
            'status' => LeadReservatorioItem::STATUS_DISPONIVEL,
            'entrou_por' => $userId,
            'entrou_em' => now(),
        ]);
    }

    public function queryDisponiveis(int $empresaId, array $condicoes = []): Builder
    {
        $vendaValidaIds = $this->vendaValidaIds($empresaId);
        $query = DB::table('lead_reservatorio_itens as l')
            ->join('contatos as c', function ($join) {
                $join->on('c.id', '=', 'l.contato_id')
                    ->on('c.empresa_id', '=', 'l.empresa_id');
            })
            ->where('l.empresa_id', $empresaId)
            ->where('l.status', LeadReservatorioItem::STATUS_DISPONIVEL)
            ->where('c.empresa_id', $empresaId)
            ->where('c.status', 'Y')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('contatos_corretores as cc')
                ->whereColumn('cc.contato_id', 'c.id')
                ->where('cc.empresa_id', $empresaId))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('preditiva as p')
                ->whereColumn('p.contato_id', 'c.id')
                ->where('p.empresa_id', $empresaId)
                ->where('p.status', 'Y'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('vendas as v')
                ->whereColumn('v.contato_id', 'c.id')
                ->where('v.empresa_id', $empresaId)
                ->whereIn('v.tabulacao_id', $vendaValidaIds));

        return $this->aplicarCondicoes($query, $condicoes);
    }

    public function preview(int $empresaId, array $condicoes): array
    {
        $this->validarCondicoes($condicoes);
        $this->sincronizarBloqueados($empresaId);

        return [
            'total_elegivel' => $this->queryDisponiveis($empresaId, $condicoes)->count(),
            'condicoes' => $condicoes,
        ];
    }

    public function executar(
        int $empresaId,
        int $estrategiaId,
        array $distribuicoes,
        int $userId
    ): LeadReservatorioExecucao {
        $estrategia = LeadReservatorioEstrategia::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->findOrFail($estrategiaId);

        $this->validarCondicoes($estrategia->condicoes);

        return $this->executarDistribuicao(
            $empresaId,
            $estrategia,
            $estrategia->condicoes,
            $distribuicoes,
            $userId,
            'DISTRIBUICAO',
        );
    }

    public function previewAleatoria(int $empresaId): array
    {
        $this->sincronizarBloqueados($empresaId);

        return [
            'total_elegivel' => $this->queryDisponiveis($empresaId)->count(),
            'condicoes' => [],
        ];
    }

    public function executarAleatoria(int $empresaId, array $distribuicoes, int $userId): LeadReservatorioExecucao
    {
        return $this->executarDistribuicao(
            $empresaId,
            null,
            [],
            $distribuicoes,
            $userId,
            'DISTRIBUICAO_ALEATORIA',
        );
    }

    private function executarDistribuicao(
        int $empresaId,
        ?LeadReservatorioEstrategia $estrategia,
        array $condicoes,
        array $distribuicoes,
        int $userId,
        string $tipo
    ): LeadReservatorioExecucao {
        $this->sincronizarBloqueados($empresaId);
        $vendedores = $this->validarDistribuicoes($empresaId, $distribuicoes);
        $total = collect($distribuicoes)->sum('quantidade');

        return Cache::lock("reservatorio-distribuicao-{$empresaId}", 30)->block(5, function () use (
            $empresaId, $estrategia, $condicoes, $distribuicoes, $vendedores, $total, $userId, $tipo
        ) {
            return DB::transaction(function () use ($empresaId, $estrategia, $condicoes, $distribuicoes, $vendedores, $total, $userId, $tipo) {
                $semente = random_int(1, 2147483647);
                $itens = $this->queryDisponiveis($empresaId, $condicoes)
                    ->select('l.id', 'l.contato_id')
                    ->orderByRaw('RAND(?)', [$semente])
                    ->limit($total)
                    ->lockForUpdate()
                    ->get();

                if ($itens->count() < $total) {
                    throw ValidationException::withMessages([
                        'distribuicoes' => 'O reservatório mudou desde a prévia. Atualize a disponibilidade e ajuste as quantidades.',
                    ]);
                }

                $novosClientesId = $this->tabulations->id($empresaId, TabulationCode::NOVOS_CLIENTES);
                $tabulacaoExiste = DB::table('tabulacoes')
                    ->where('empresa_id', $empresaId)
                    ->where('id', $novosClientesId)
                    ->where('status', 'Y')
                    ->where('tipo_tabulacao', 'C')
                    ->exists();

                if (! $tabulacaoExiste) {
                    throw ValidationException::withMessages([
                        'tabulacao' => 'A tabulação NOVOS CLIENTES não está ativa para esta empresa.',
                    ]);
                }

                $execucao = LeadReservatorioExecucao::create([
                    'empresa_id' => $empresaId,
                    'estrategia_id' => $estrategia?->id,
                    'tipo' => $tipo,
                    'status' => 'EM_EXECUCAO',
                    'filtros_snapshot' => $condicoes,
                    'distribuicoes' => $distribuicoes,
                    'semente' => (string) $semente,
                    'total_solicitado' => $total,
                    'created_by' => $userId,
                ]);

                $indice = 0;
                foreach ($distribuicoes as $distribuicao) {
                    $vendedor = $vendedores[(int) $distribuicao['vendedor_id']];
                    for ($i = 0; $i < (int) $distribuicao['quantidade']; $i++) {
                        $item = $itens[$indice++];
                        ContatosCorretores::create([
                            'empresa_id' => $empresaId,
                            'contato_id' => $item->contato_id,
                            'user_id' => $vendedor->id,
                            'tabulacao_id' => $novosClientesId,
                            'temperatura' => 'FRIO',
                        ]);

                        LeadReservatorioItem::query()
                            ->whereKey($item->id)
                            ->where('empresa_id', $empresaId)
                            ->update([
                                'status' => LeadReservatorioItem::STATUS_DISTRIBUIDO,
                                'distribuido_para' => $vendedor->id,
                                'distribuido_por' => $userId,
                                'distribuido_em' => now(),
                                'updated_at' => now(),
                            ]);

                        LeadReservatorioExecucaoItem::create([
                            'execucao_id' => $execucao->id,
                            'reservatorio_item_id' => $item->id,
                            'contato_id' => $item->contato_id,
                            'vendedor_id' => $vendedor->id,
                            'resultado' => 'DISTRIBUIDO',
                        ]);
                    }
                }

                $execucao->update([
                    'status' => 'CONCLUIDA',
                    'total_executado' => $total,
                    'executada_em' => now(),
                ]);

                return $execucao->fresh();
            });
        });
    }

    public function previewMigracao(int $empresaId, int $vendedorId): array
    {
        $remarketingId = $this->tabulations->id($empresaId, TabulationCode::REMARKETING);
        $negocioFechadoId = $this->tabulations->id($empresaId, TabulationCode::NEGOCIO_FECHADO);
        $vendaValidaIds = $this->vendaValidaIds($empresaId);
        $this->vendedorDaEmpresa($empresaId, $vendedorId, false);
        $base = $this->queryCarteiraOrigem($empresaId, $vendedorId);
        $total = (clone $base)->distinct()->count('c.id');
        $descartados = (clone $base)->where('c.status', '!=', 'Y')->distinct()->count('c.id');
        $remarketing = (clone $base)->where('cc.tabulacao_id', $remarketingId)->distinct()->count('c.id');
        $fechados = (clone $base)->where('cc.tabulacao_id', $negocioFechadoId)->distinct()->count('c.id');
        $vendidos = (clone $base)->whereExists(fn ($q) => $q->selectRaw('1')->from('vendas as v')
            ->whereColumn('v.contato_id', 'c.id')->where('v.empresa_id', $empresaId)
            ->whereIn('v.tabulacao_id', $vendaValidaIds))->distinct()->count('c.id');
        $preditiva = (clone $base)->whereExists(fn ($q) => $q->selectRaw('1')->from('preditiva as p')
            ->whereColumn('p.contato_id', 'c.id')->where('p.empresa_id', $empresaId)->where('p.status', 'Y'))
            ->distinct()->count('c.id');
        $jaRegistrados = (clone $base)->whereExists(fn ($q) => $q->selectRaw('1')->from('lead_reservatorio_itens as l')
            ->whereColumn('l.contato_id', 'c.id')->where('l.empresa_id', $empresaId))->distinct()->count('c.id');
        $conflitos = (clone $base)->whereExists(fn ($q) => $q->selectRaw('1')->from('contatos_corretores as outro')
            ->whereColumn('outro.contato_id', 'c.id')->where('outro.empresa_id', $empresaId)
            ->whereColumn('outro.id', '!=', 'cc.id'))->distinct()->count('c.id');
        $aptos = $this->queryCarteiraElegivel($empresaId, $vendedorId)->distinct()->count('c.id');

        return compact('total', 'aptos', 'descartados', 'remarketing', 'fechados', 'vendidos', 'preditiva', 'jaRegistrados', 'conflitos');
    }

    public function migrarCarteiraInicial(int $empresaId, int $vendedorId, int $userId): LeadReservatorioExecucao
    {
        $vendedor = $this->vendedorDaEmpresa($empresaId, $vendedorId, false);
        $chave = "MIGRACAO_INICIAL:{$empresaId}";

        return Cache::lock("reservatorio-migracao-{$empresaId}", 60)->block(5, function () use (
            $empresaId, $vendedor, $userId, $chave
        ) {
            return DB::transaction(function () use ($empresaId, $vendedor, $userId, $chave) {
                if (LeadReservatorioExecucao::where('chave_idempotencia', $chave)->exists()) {
                    throw ValidationException::withMessages([
                        'vendedor_id' => 'A carga inicial do reservatório já foi concluída para esta empresa.',
                    ]);
                }

                $preview = $this->previewMigracao($empresaId, $vendedor->id);
                $vinculos = $this->queryCarteiraElegivel($empresaId, $vendedor->id)
                    ->select('cc.id as vinculo_id', 'c.id as contato_id')
                    ->orderBy('c.id')
                    ->lockForUpdate()
                    ->get();

                if ($vinculos->isEmpty()) {
                    throw ValidationException::withMessages([
                        'vendedor_id' => 'Nenhum lead apto foi encontrado com este vendedor.',
                    ]);
                }

                $execucao = LeadReservatorioExecucao::create([
                    'empresa_id' => $empresaId,
                    'tipo' => 'MIGRACAO_INICIAL',
                    'status' => 'EM_EXECUCAO',
                    'total_solicitado' => $vinculos->count(),
                    'total_ignorado' => max(0, $preview['total'] - $preview['aptos']),
                    'vendedor_origem_id' => $vendedor->id,
                    'created_by' => $userId,
                    'chave_idempotencia' => $chave,
                ]);

                foreach ($vinculos as $vinculo) {
                    DB::table('contatos_corretores')->where('id', $vinculo->vinculo_id)->delete();
                    $item = LeadReservatorioItem::create([
                        'empresa_id' => $empresaId,
                        'contato_id' => $vinculo->contato_id,
                        'origem' => LeadReservatorioItem::ORIGEM_MIGRACAO,
                        'status' => LeadReservatorioItem::STATUS_DISPONIVEL,
                        'entrou_por' => $userId,
                        'entrou_em' => now(),
                    ]);
                    LeadReservatorioExecucaoItem::create([
                        'execucao_id' => $execucao->id,
                        'reservatorio_item_id' => $item->id,
                        'contato_id' => $vinculo->contato_id,
                        'resultado' => 'MIGRADO',
                    ]);
                }

                $execucao->update([
                    'status' => 'CONCLUIDA',
                    'total_executado' => $vinculos->count(),
                    'executada_em' => now(),
                ]);

                return $execucao->fresh();
            });
        });
    }

    private function contatoPodeEntrar(int $empresaId, int $contatoId): bool
    {
        $vendaValidaIds = $this->vendaValidaIds($empresaId);

        return DB::table('contatos as c')
            ->where('c.id', $contatoId)
            ->where('c.empresa_id', $empresaId)
            ->where('c.status', 'Y')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('contatos_corretores as cc')
                ->whereColumn('cc.contato_id', 'c.id')->where('cc.empresa_id', $empresaId))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('preditiva as p')
                ->whereColumn('p.contato_id', 'c.id')->where('p.empresa_id', $empresaId)->where('p.status', 'Y'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('vendas as v')
                ->whereColumn('v.contato_id', 'c.id')->where('v.empresa_id', $empresaId)
                ->whereIn('v.tabulacao_id', $vendaValidaIds))
            ->exists();
    }

    public function sincronizarBloqueados(int $empresaId): int
    {
        $vendaValidaIds = $this->vendaValidaIds($empresaId);
        $agora = now();
        $bloqueados = 0;

        $bloqueados += DB::table('lead_reservatorio_itens as l')
            ->join('contatos as c', function ($join) {
                $join->on('c.id', '=', 'l.contato_id')
                    ->on('c.empresa_id', '=', 'l.empresa_id');
            })
            ->where('l.empresa_id', $empresaId)
            ->where('l.status', LeadReservatorioItem::STATUS_DISPONIVEL)
            ->where('c.status', '!=', 'Y')
            ->update([
                'l.status' => LeadReservatorioItem::STATUS_BLOQUEADO,
                'l.bloqueado_motivo' => 'CONTATO_DESCARTADO',
                'l.updated_at' => $agora,
            ]);

        $bloqueados += DB::table('lead_reservatorio_itens as l')
            ->where('l.empresa_id', $empresaId)
            ->where('l.status', LeadReservatorioItem::STATUS_DISPONIVEL)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('contatos_corretores as cc')
                ->whereColumn('cc.contato_id', 'l.contato_id')->where('cc.empresa_id', $empresaId))
            ->update([
                'status' => LeadReservatorioItem::STATUS_BLOQUEADO,
                'bloqueado_motivo' => 'JA_ATRIBUIDO',
                'updated_at' => $agora,
            ]);

        $bloqueados += DB::table('lead_reservatorio_itens as l')
            ->where('l.empresa_id', $empresaId)
            ->where('l.status', LeadReservatorioItem::STATUS_DISPONIVEL)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('preditiva as p')
                ->whereColumn('p.contato_id', 'l.contato_id')->where('p.empresa_id', $empresaId)->where('p.status', 'Y'))
            ->update([
                'status' => LeadReservatorioItem::STATUS_BLOQUEADO,
                'bloqueado_motivo' => 'PREDITIVA',
                'updated_at' => $agora,
            ]);

        $bloqueados += DB::table('lead_reservatorio_itens as l')
            ->where('l.empresa_id', $empresaId)
            ->where('l.status', LeadReservatorioItem::STATUS_DISPONIVEL)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('vendas as v')
                ->whereColumn('v.contato_id', 'l.contato_id')->where('v.empresa_id', $empresaId)
                ->whereIn('v.tabulacao_id', $vendaValidaIds))
            ->update([
                'status' => LeadReservatorioItem::STATUS_BLOQUEADO,
                'bloqueado_motivo' => 'VENDA_EXISTENTE',
                'updated_at' => $agora,
            ]);

        return $bloqueados;
    }

    private function aplicarCondicoes(Builder $query, array $condicoes): Builder
    {
        foreach ($condicoes as $condicao) {
            $campo = self::CAMPOS[$condicao['campo']];
            $coluna = $campo['coluna'];
            $operador = $condicao['operador'];
            $valor = $condicao['valor'] ?? null;

            if ($operador === 'igual') {
                $query->where($coluna, $this->normalizarBooleano($campo['tipo'], $valor));
            } elseif ($operador === 'contem') {
                $query->where($coluna, 'like', '%'.$valor.'%');
            } elseif ($operador === 'em') {
                $query->whereIn($coluna, (array) $valor);
            } elseif ($operador === 'preenchido') {
                $query->whereNotNull($coluna)->where($coluna, '!=', '');
            } elseif ($operador === 'vazio') {
                $query->where(fn ($q) => $q->whereNull($coluna)->orWhere($coluna, ''));
            } elseif ($operador === 'maior_ou_igual') {
                $query->where($coluna, '>=', $valor);
            } elseif ($operador === 'menor_ou_igual') {
                $query->where($coluna, '<=', $valor);
            } elseif ($operador === 'entre') {
                $query->whereBetween($coluna, [$valor[0], $valor[1]]);
            }
        }

        return $query;
    }

    private function validarCondicoes(array $condicoes): void
    {
        if ($condicoes === [] || count($condicoes) > 15) {
            throw ValidationException::withMessages(['condicoes' => 'Informe de 1 a 15 condições.']);
        }

        $operadores = [
            'texto' => ['igual', 'contem', 'em', 'preenchido', 'vazio'],
            'lista' => ['igual', 'em'],
            'booleano' => ['igual'],
            'numero' => ['igual', 'maior_ou_igual', 'menor_ou_igual', 'entre', 'preenchido', 'vazio'],
            'data' => ['igual', 'maior_ou_igual', 'menor_ou_igual', 'entre'],
        ];

        foreach ($condicoes as $indice => $condicao) {
            $campo = $condicao['campo'] ?? null;
            $operador = $condicao['operador'] ?? null;
            if (! isset(self::CAMPOS[$campo]) || ! in_array($operador, $operadores[self::CAMPOS[$campo]['tipo']], true)) {
                throw ValidationException::withMessages(["condicoes.{$indice}" => 'Campo ou operador não permitido.']);
            }
            if (! in_array($operador, ['preenchido', 'vazio'], true) && ! array_key_exists('valor', $condicao)) {
                throw ValidationException::withMessages(["condicoes.{$indice}.valor" => 'Informe o valor da condição.']);
            }
            if ($operador === 'entre' && (! is_array($condicao['valor']) || count($condicao['valor']) !== 2)) {
                throw ValidationException::withMessages(["condicoes.{$indice}.valor" => 'O intervalo precisa de início e fim.']);
            }
        }
    }

    private function validarDistribuicoes(int $empresaId, array $distribuicoes): array
    {
        $ids = collect($distribuicoes)->pluck('vendedor_id')->map(fn ($id) => (int) $id);
        if ($ids->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['distribuicoes' => 'Cada vendedor pode aparecer apenas uma vez.']);
        }

        return $ids->mapWithKeys(function (int $id) use ($empresaId) {
            $vendedor = $this->vendedorDaEmpresa($empresaId, $id, true);

            return [$id => $vendedor];
        })->all();
    }

    private function vendedorDaEmpresa(int $empresaId, int $vendedorId, bool $ativo): User
    {
        $query = User::query()
            ->tenantMember($empresaId)
            ->where('user_role_id', UserRole::VENDEDOR);
        if ($ativo) {
            $query->where('ativo', 'Y');
        }

        $vendedor = $query->find($vendedorId);
        if (! $vendedor) {
            throw ValidationException::withMessages(['vendedor_id' => 'Vendedor inválido para esta empresa.']);
        }

        return $vendedor;
    }

    private function queryCarteiraOrigem(int $empresaId, int $vendedorId): Builder
    {
        return DB::table('contatos_corretores as cc')
            ->join('contatos as c', function ($join) {
                $join->on('c.id', '=', 'cc.contato_id')
                    ->on('c.empresa_id', '=', 'cc.empresa_id');
            })
            ->where('cc.empresa_id', $empresaId)
            ->where('c.empresa_id', $empresaId)
            ->where('cc.user_id', $vendedorId);
    }

    private function queryCarteiraElegivel(int $empresaId, int $vendedorId): Builder
    {
        $idsExcluidos = array_values($this->tabulations->ids($empresaId, [
            TabulationCode::REMARKETING,
            TabulationCode::NEGOCIO_FECHADO,
        ]));
        $vendaValidaIds = $this->vendaValidaIds($empresaId);

        return $this->queryCarteiraOrigem($empresaId, $vendedorId)
            ->where('c.status', 'Y')
            ->whereNotIn('cc.tabulacao_id', $idsExcluidos)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('vendas as v')
                ->whereColumn('v.contato_id', 'c.id')->where('v.empresa_id', $empresaId)
                ->whereIn('v.tabulacao_id', $vendaValidaIds))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('preditiva as p')
                ->whereColumn('p.contato_id', 'c.id')->where('p.empresa_id', $empresaId)->where('p.status', 'Y'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('lead_reservatorio_itens as l')
                ->whereColumn('l.contato_id', 'c.id')->where('l.empresa_id', $empresaId))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('contatos_corretores as outro')
                ->whereColumn('outro.contato_id', 'c.id')->where('outro.empresa_id', $empresaId)
                ->whereColumn('outro.id', '!=', 'cc.id'));
    }

    private function normalizarBooleano(string $tipo, mixed $valor): mixed
    {
        if ($tipo !== 'booleano') {
            return $valor;
        }

        if (in_array(strtoupper((string) $valor), ['Y', 'S', 'SIM', '1', 'TRUE'], true)) {
            return 'Y';
        }

        return 'N';
    }

    private function vendaValidaIds(int $empresaId): array
    {
        return array_values($this->tabulations->requiredIds($empresaId, TabulationCode::POS_VENDA_ELEGIVEIS));
    }
}
