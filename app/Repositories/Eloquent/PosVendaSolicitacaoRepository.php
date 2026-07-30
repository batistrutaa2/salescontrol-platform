<?php

namespace App\Repositories\Eloquent;

use App\Enums\NaturezaEtapaSolicitacao;
use App\Enums\TipoSolicitacaoPosVenda;
use App\Models\PosVendaFluxoEtapa;
use App\Models\PosVendaSolicitacao;
use App\Models\PosVendaSolicitacaoHistorico;
use App\Models\Vendas;
use App\Repositories\Contracts\PosVendaSolicitacaoRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PosVendaSolicitacaoRepository implements PosVendaSolicitacaoRepositoryInterface
{
    public function listar(int $empresaId, array $filtros = []): array
    {
        $query = PosVendaSolicitacao::with([
            'venda:id,nome_contrato,cpf_cnpj,operadora',
            'etapa:id,nome,cor,natureza,ordem',
            'responsavel:id,name',
            'criador:id,name',
        ])
            ->where('empresa_id', $empresaId);

        if (! empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }
        if (! empty($filtros['responsavel_id'])) {
            $query->where('responsavel_id', (int) $filtros['responsavel_id']);
        }
        if (! empty($filtros['etapa_id'])) {
            $query->where('etapa_id', (int) $filtros['etapa_id']);
        }
        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }
        if (! empty($filtros['busca'])) {
            $like = '%'.$this->escaparLike(trim($filtros['busca'])).'%';
            $query->where(function ($q) use ($like) {
                $q->where('titulo', 'like', $like)
                    ->orWhereHas('venda', function ($v) use ($like) {
                        $v->where('nome_contrato', 'like', $like)
                            ->orWhere('cpf_cnpj', 'like', $like);
                    });
            });
        }

        // Fila por urgência: abertas primeiro (sem prazo no topo — precisam de
        // triagem —, depois vencidas, hoje, próximas); encerradas por último.
        return $query
            ->orderByRaw("(status = 'ABERTA') desc")
            ->orderByRaw('(data_limite is null) desc')
            ->orderBy('data_limite')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PosVendaSolicitacao $s) => $this->linha($s))
            ->all();
    }

    public function kpis(int $empresaId): array
    {
        $hoje = Carbon::today()->toDateString();
        $base = fn () => PosVendaSolicitacao::where('empresa_id', $empresaId);

        return [
            'atrasadas' => $base()->where('status', PosVendaSolicitacao::STATUS_ABERTA)
                ->whereNotNull('data_limite')->whereDate('data_limite', '<', $hoje)->count(),
            'vencem_hoje' => $base()->where('status', PosVendaSolicitacao::STATUS_ABERTA)
                ->whereDate('data_limite', $hoje)->count(),
            'abertas' => $base()->where('status', PosVendaSolicitacao::STATUS_ABERTA)->count(),
            'concluidas_mes' => $base()->where('status', PosVendaSolicitacao::STATUS_CONCLUIDA)
                ->whereBetween('concluida_em', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count(),
        ];
    }

    public function criar(int $empresaId, array $dados, int $userId): ?PosVendaSolicitacao
    {
        $venda = Vendas::where('id', $dados['venda_id'])
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $venda) {
            return null;
        }

        $tipo = TipoSolicitacaoPosVenda::from($dados['tipo']);
        $etapaInicial = $this->etapaInicial($empresaId, $tipo->value);

        return DB::transaction(function () use ($empresaId, $dados, $userId, $tipo, $etapaInicial) {
            $solicitacao = PosVendaSolicitacao::create([
                'venda_id' => $dados['venda_id'],
                'empresa_id' => $empresaId,
                'tipo' => $tipo->value,
                'etapa_id' => $etapaInicial->id,
                'titulo' => $dados['titulo'] ?? $tipo->label(),
                'descricao' => $dados['descricao'] ?? null,
                'status' => PosVendaSolicitacao::STATUS_ABERTA,
                'data_limite' => $dados['data_limite'] ?? null,
                'origem' => $dados['origem'] ?? PosVendaSolicitacao::ORIGEM_BACKOFFICE,
                'responsavel_id' => $dados['responsavel_id'] ?? null,
                'created_by' => $userId,
            ]);

            PosVendaSolicitacaoHistorico::create([
                'solicitacao_id' => $solicitacao->id,
                'user_id' => $userId,
                'campo_alterado' => 'abertura',
                'valor_anterior' => null,
                'valor_novo' => $etapaInicial->nome,
                'observacao' => $dados['observacao_abertura'] ?? 'Solicitação aberta.',
            ]);

            return $solicitacao;
        });
    }

    public function detalhe(int $id, int $empresaId): ?array
    {
        $solicitacao = PosVendaSolicitacao::with([
            'venda:id,nome_contrato,cpf_cnpj,operadora,nome_plano,numero_proposta',
            'etapa:id,nome,cor,natureza,ordem',
            'responsavel:id,name',
            'criador:id,name',
            'historico.usuario:id,name',
        ])
            ->where('empresa_id', $empresaId)
            ->find($id);

        if (! $solicitacao) {
            return null;
        }

        return [
            'registro' => $this->linha($solicitacao) + [
                'descricao' => $solicitacao->descricao,
                'nome_plano' => $solicitacao->venda?->nome_plano,
                'numero_proposta' => $solicitacao->venda?->numero_proposta,
            ],
            'historico' => $solicitacao->historico->map(fn (PosVendaSolicitacaoHistorico $h) => [
                'campo_alterado' => $h->campo_alterado,
                'valor_anterior' => $h->valor_anterior,
                'valor_novo' => $h->valor_novo,
                'observacao' => $h->observacao,
                'usuario_nome' => $h->usuario?->name,
                'created_at' => $h->getRawOriginal('created_at')
                    ? Carbon::parse($h->getRawOriginal('created_at'))->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i')
                    : '—',
            ])->all(),
        ];
    }

    public function atualizar(int $id, int $empresaId, array $dados, int $userId): bool
    {
        $solicitacao = PosVendaSolicitacao::with('responsavel:id,name')
            ->where('empresa_id', $empresaId)
            ->find($id);
        if (! $solicitacao) {
            return false;
        }

        DB::transaction(function () use ($solicitacao, $dados, $userId) {
            if (array_key_exists('data_limite', $dados)) {
                $novo = $dados['data_limite'] ? Carbon::parse($dados['data_limite'])->startOfDay() : null;
                $atual = $solicitacao->data_limite?->startOfDay();

                if (($novo?->toDateString()) !== ($atual?->toDateString())) {
                    $this->registrarHistorico($solicitacao->id, $userId, 'prazo',
                        $atual?->format('d/m/Y') ?? 'Sem prazo',
                        $novo?->format('d/m/Y') ?? 'Sem prazo');
                    $solicitacao->data_limite = $novo;
                }
            }

            if (array_key_exists('responsavel_id', $dados)
                && (int) $solicitacao->responsavel_id !== (int) ($dados['responsavel_id'] ?? 0)) {
                $nomeAnterior = $solicitacao->responsavel?->name;
                $solicitacao->responsavel_id = $dados['responsavel_id'] ?: null;
                $solicitacao->load('responsavel:id,name');
                $this->registrarHistorico($solicitacao->id, $userId, 'responsavel',
                    $nomeAnterior ?? 'Sem responsável',
                    $solicitacao->responsavel?->name ?? 'Sem responsável');
            }

            foreach (['titulo' => 'titulo', 'descricao' => 'observacoes'] as $campo => $label) {
                if (! array_key_exists($campo, $dados) || $dados[$campo] === $solicitacao->$campo) {
                    continue;
                }
                $this->registrarHistorico($solicitacao->id, $userId, $label,
                    $solicitacao->$campo !== null ? mb_substr((string) $solicitacao->$campo, 0, 255) : null,
                    $dados[$campo] !== null ? mb_substr((string) $dados[$campo], 0, 255) : null);
                $solicitacao->$campo = $dados[$campo];
            }

            $solicitacao->save();
        });

        return true;
    }

    public function moverEtapa(int $id, int $empresaId, int $etapaId, int $userId, ?string $observacao = null): ?string
    {
        $solicitacao = PosVendaSolicitacao::with('etapa')
            ->where('empresa_id', $empresaId)
            ->find($id);
        if (! $solicitacao) {
            return 'NAO_ENCONTRADA';
        }

        $novaEtapa = PosVendaFluxoEtapa::where('empresa_id', $empresaId)->find($etapaId);
        if (! $novaEtapa) {
            return 'NAO_ENCONTRADA';
        }

        if ($novaEtapa->tipo !== $solicitacao->tipo) {
            return 'ETAPA_INVALIDA';
        }

        if ((int) $solicitacao->etapa_id === (int) $novaEtapa->id) {
            return null;
        }

        DB::transaction(function () use ($solicitacao, $novaEtapa, $userId, $observacao) {
            $this->registrarHistorico($solicitacao->id, $userId, 'etapa',
                $solicitacao->etapa?->nome, $novaEtapa->nome, $observacao);

            $solicitacao->etapa_id = $novaEtapa->id;

            $natureza = NaturezaEtapaSolicitacao::tryFrom($novaEtapa->natureza) ?? NaturezaEtapaSolicitacao::EM_ANDAMENTO;
            if ($natureza === NaturezaEtapaSolicitacao::CONCLUIDA) {
                $solicitacao->status = PosVendaSolicitacao::STATUS_CONCLUIDA;
                $solicitacao->concluida_em = now();
            } elseif ($natureza === NaturezaEtapaSolicitacao::CANCELADA) {
                $solicitacao->status = PosVendaSolicitacao::STATUS_CANCELADA;
                $solicitacao->concluida_em = now();
            } else {
                $solicitacao->status = PosVendaSolicitacao::STATUS_ABERTA;
                $solicitacao->concluida_em = null;
            }

            $solicitacao->save();
        });

        return null;
    }

    public function buscarContratos(string $termo, int $empresaId, int $limite = 20): array
    {
        $termo = trim(preg_replace('/\s+/', ' ', $termo));
        if (mb_strlen($termo) < 2) {
            return [];
        }

        $like = '%'.$this->escaparLike($termo).'%';
        $digitos = preg_replace('/\D+/', '', $termo);

        return Vendas::with(['user:id,name', 'tabulacao:id,descricao', 'titulares:id,venda_id,nome'])
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($like, $digitos) {
                $q->where('nome_contrato', 'like', $like)
                    ->orWhere('numero_proposta', 'like', $like)
                    ->orWhere('cpf_cnpj', 'like', $like)
                    // Cliente pode ser achado pelo beneficiário (nome ou CPF do titular).
                    ->orWhereHas('titulares', function ($t) use ($like, $digitos) {
                        $t->where('nome', 'like', $like);
                        if ($digitos !== '') {
                            $t->orWhere('cpf', 'like', "%{$digitos}%");
                        }
                    });
                if ($digitos !== '') {
                    $q->orWhere('cpf_cnpj', 'like', "%{$digitos}%");
                }
            })
            ->orderByDesc('id')
            ->limit($limite)
            ->get([
                'id', 'nome_contrato', 'cpf_cnpj', 'numero_proposta', 'operadora', 'nome_plano',
                'valor_contrato', 'vidas', 'telefone1', 'data_vigencia', 'data_implantacao',
                'user_id', 'tabulacao_id',
            ])
            ->map(fn (Vendas $v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'cpf_cnpj' => $v->cpf_cnpj,
                'numero_proposta' => $v->numero_proposta,
                'operadora' => $v->operadora,
                'nome_plano' => $v->nome_plano,
                'valor_contrato' => $v->valor_contrato !== null
                    ? 'R$ '.number_format((float) $v->valor_contrato, 2, ',', '.')
                    : null,
                'vidas' => $v->vidas,
                'telefone' => $v->telefone1,
                'status' => $v->tabulacao?->descricao,
                'vendedor_nome' => $v->user?->name,
                'titulares' => $v->titulares->pluck('nome')->all(),
                'data_vigencia' => $v->data_vigencia?->format('d/m/Y'),
                'data_implantacao' => $v->data_implantacao?->format('d/m/Y'),
            ])
            ->all();
    }

    // ---------------------------------------------------------------
    // Etapas do fluxo (colunas do kanban, configuráveis por tipo)
    // ---------------------------------------------------------------

    public function etapas(int $empresaId): array
    {
        $porTipo = [];
        foreach (TipoSolicitacaoPosVenda::cases() as $tipo) {
            $porTipo[$tipo->value] = [];
        }

        PosVendaFluxoEtapa::where('empresa_id', $empresaId)
            ->orderBy('tipo')->orderBy('ordem')->orderBy('id')
            ->get()
            ->each(function (PosVendaFluxoEtapa $e) use (&$porTipo) {
                $porTipo[$e->tipo][] = [
                    'id' => $e->id,
                    'nome' => $e->nome,
                    'cor' => $e->cor,
                    'ordem' => $e->ordem,
                    'natureza' => $e->natureza,
                ];
            });

        return $porTipo;
    }

    public function etapaInicial(int $empresaId, string $tipo): PosVendaFluxoEtapa
    {
        $buscar = fn () => PosVendaFluxoEtapa::where('empresa_id', $empresaId)
            ->where('tipo', $tipo)
            ->where('natureza', NaturezaEtapaSolicitacao::EM_ANDAMENTO->value)
            ->orderBy('ordem')->orderBy('id')
            ->first();

        $etapa = $buscar();
        if (! $etapa) {
            PosVendaFluxoEtapa::seedDefaults($empresaId);
            $etapa = $buscar();
        }

        return $etapa;
    }

    public function criarEtapa(int $empresaId, array $dados): PosVendaFluxoEtapa
    {
        $ultimaOrdem = (int) PosVendaFluxoEtapa::where('empresa_id', $empresaId)
            ->where('tipo', $dados['tipo'])
            ->max('ordem');

        return PosVendaFluxoEtapa::create([
            'empresa_id' => $empresaId,
            'tipo' => $dados['tipo'],
            'nome' => $dados['nome'],
            'cor' => $dados['cor'] ?? null,
            'ordem' => $ultimaOrdem + 1,
            'natureza' => $dados['natureza'] ?? NaturezaEtapaSolicitacao::EM_ANDAMENTO->value,
        ]);
    }

    public function atualizarEtapa(int $id, int $empresaId, array $dados): bool
    {
        $etapa = PosVendaFluxoEtapa::where('empresa_id', $empresaId)->find($id);
        if (! $etapa) {
            return false;
        }

        $etapa->fill(array_intersect_key($dados, array_flip(['nome', 'cor', 'natureza'])));
        $etapa->save();

        return true;
    }

    public function excluirEtapa(int $id, int $empresaId): ?string
    {
        $etapa = PosVendaFluxoEtapa::where('empresa_id', $empresaId)->find($id);
        if (! $etapa) {
            return 'NAO_ENCONTRADA';
        }

        if ($etapa->solicitacoes()->exists()) {
            return 'COM_SOLICITACOES';
        }

        // Todo tipo precisa manter ao menos uma etapa de conclusão para o fluxo fechar.
        if ($etapa->natureza === NaturezaEtapaSolicitacao::CONCLUIDA->value) {
            $outrasConcluidas = PosVendaFluxoEtapa::where('empresa_id', $empresaId)
                ->where('tipo', $etapa->tipo)
                ->where('natureza', NaturezaEtapaSolicitacao::CONCLUIDA->value)
                ->where('id', '!=', $etapa->id)
                ->exists();

            if (! $outrasConcluidas) {
                return 'ULTIMA_CONCLUIDA';
            }
        }

        $etapa->delete();

        return null;
    }

    public function reordenarEtapas(int $empresaId, string $tipo, array $ordemIds): bool
    {
        $etapas = PosVendaFluxoEtapa::where('empresa_id', $empresaId)
            ->where('tipo', $tipo)
            ->pluck('id')
            ->all();

        // A nova ordem precisa cobrir exatamente as etapas do tipo.
        if (count($ordemIds) !== count($etapas) || array_diff($etapas, $ordemIds)) {
            return false;
        }

        DB::transaction(function () use ($ordemIds, $empresaId) {
            foreach (array_values($ordemIds) as $ordem => $id) {
                PosVendaFluxoEtapa::where('empresa_id', $empresaId)
                    ->where('id', $id)
                    ->update(['ordem' => $ordem]);
            }
        });

        return true;
    }

    // ---------------------------------------------------------------

    /** Uma linha da central (fila e kanban compartilham o formato). */
    private function linha(PosVendaSolicitacao $s): array
    {
        $dataLimite = $s->data_limite;
        $diasRestantes = null;
        if ($dataLimite && $s->status === PosVendaSolicitacao::STATUS_ABERTA) {
            $diasRestantes = (int) Carbon::today()->diffInDays($dataLimite->startOfDay(), false);
        }

        return [
            'id' => $s->id,
            'venda_id' => $s->venda_id,
            'nome_contrato' => $s->venda?->nome_contrato ?? '—',
            'cpf_cnpj' => $s->venda?->cpf_cnpj,
            'operadora' => $s->venda?->operadora,
            'tipo' => $s->tipo,
            'tipo_label' => TipoSolicitacaoPosVenda::tryFrom($s->tipo)?->label() ?? $s->tipo,
            'titulo' => $s->titulo,
            'etapa_id' => $s->etapa_id,
            'etapa_nome' => $s->etapa?->nome,
            'etapa_cor' => $s->etapa?->cor,
            'etapa_natureza' => $s->etapa?->natureza,
            'status' => $s->status,
            'data_limite' => $dataLimite?->format('d/m/Y'),
            'data_limite_iso' => $dataLimite?->format('Y-m-d'),
            'dias_restantes' => $diasRestantes,
            'origem' => $s->origem,
            'criado_por_nome' => $s->criador?->name,
            'responsavel_id' => $s->responsavel_id,
            'responsavel_nome' => $s->responsavel?->name,
            'concluida_em' => $s->concluida_em?->setTimezone('America/Sao_Paulo')->format('d/m/Y'),
            'criado_em' => $s->getRawOriginal('created_at')
                ? Carbon::parse($s->getRawOriginal('created_at'))->setTimezone('America/Sao_Paulo')->format('d/m/Y')
                : '—',
        ];
    }

    private function registrarHistorico(int $solicitacaoId, int $userId, string $campo, ?string $anterior, ?string $novo, ?string $observacao = null): void
    {
        PosVendaSolicitacaoHistorico::create([
            'solicitacao_id' => $solicitacaoId,
            'user_id' => $userId,
            'campo_alterado' => $campo,
            'valor_anterior' => $anterior,
            'valor_novo' => $novo,
            'observacao' => $observacao,
        ]);
    }

    /**
     * Neutraliza os curingas do LIKE — sem isso um termo com "%" ou "_"
     * vira wildcard e traz a base inteira.
     */
    private function escaparLike(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }
}
