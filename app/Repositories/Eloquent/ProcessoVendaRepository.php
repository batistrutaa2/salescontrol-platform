<?php

namespace App\Repositories\Eloquent;

use App\Enums\FaseCancelamento;
use App\Enums\FasePortabilidade;
use App\Enums\Tabulations;
use App\Enums\TipoDemandaContrato;
use App\Models\CredencialAcesso;
use App\Models\VendaDemanda;
use App\Models\VendaPortabilidade;
use App\Models\Vendas;
use App\Repositories\Contracts\ProcessoVendaRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProcessoVendaRepository implements ProcessoVendaRepositoryInterface
{
    public function daVenda(int $vendaId, int $empresaId): Collection
    {
        return VendaDemanda::with([
            'titular:id,nome,email,cpf',
            'operadoraAnterior:id,nome',
            'criador:id,name',
            'concluidaPor:id,name',
        ])
            ->where('venda_id', $vendaId)
            ->where('empresa_id', $empresaId)
            ->orderByRaw("FIELD(status, 'PENDENTE', 'CONCLUIDA')")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function resumo(int $vendaId, int $empresaId): array
    {
        $status = VendaDemanda::where('venda_id', $vendaId)
            ->where('empresa_id', $empresaId)
            ->selectRaw("SUM(status = 'PENDENTE') as pendentes, COUNT(*) as total")
            ->first();

        $total = (int) ($status->total ?? 0);
        $pendentes = (int) ($status->pendentes ?? 0);
        $concluidas = $total - $pendentes;

        return [
            'total' => $total,
            'pendentes' => $pendentes,
            'concluidas' => $concluidas,
            'progresso' => $total > 0 ? (int) round(($concluidas / $total) * 100) : 0,
        ];
    }

    public function atualizarCancelamento(int $id, int $empresaId, array $dados, ?int $userId): ?VendaDemanda
    {
        $demanda = VendaDemanda::where('id', $id)->where('empresa_id', $empresaId)->first();

        if (! $demanda) {
            return null;
        }

        $meta = $demanda->meta ?? [];
        foreach (['modalidade', 'fase', 'protocolo'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $meta[$campo] = $dados[$campo];
            }
        }

        $ehFinal = ($meta['fase'] ?? null) === FaseCancelamento::CONCLUIDO->value;

        $demanda->update([
            'meta' => $meta,
            'status' => $ehFinal ? 'CONCLUIDA' : 'PENDENTE',
            'concluida_por' => $ehFinal ? $userId : null,
            'concluida_em' => $ehFinal ? Carbon::now() : null,
        ]);

        return $demanda->fresh(['titular:id,nome', 'operadoraAnterior:id,nome', 'concluidaPor:id,name']);
    }

    // ---- Painel operacional ----

    public function filaOperacional(int $empresaId, array $filtros = []): array
    {
        $grupos = TipoDemandaContrato::grupos();

        // Corte de abertura: processos antigos (tratados fora do sistema) ficam
        // fora da fila para não exigir baixa manual. Ver config/processos.php.
        $corte = config('processos.corte_abertos');

        // O painel foca nos grupos configurados (cancelamentos e portabilidade);
        // os demais tipos seguem apenas no checklist do contrato.
        $gruposPainel = config('processos.grupos_painel', ['cancelamentos', 'portabilidade']);
        $tiposPainel = array_keys(array_filter($grupos, fn ($g) => in_array($g, $gruposPainel, true)));

        $demandas = VendaDemanda::with([
            'venda:id,nome_contrato,tabulacao_id,data_implantacao',
            'venda.tabulacao:id,descricao',
            'titular:id,nome',
            'responsavel:id,name',
        ])
            ->where('empresa_id', $empresaId)
            ->where('status', 'PENDENTE')
            ->whereIn('tipo', $tiposPainel)
            // Contratos estornados/declinados estão mortos: fora do fluxo operacional.
            ->whereDoesntHave('venda', fn ($q) => $q->whereIn('tabulacao_id', [Tabulations::ESTORNO, Tabulations::DECLINIO]))
            ->when($corte, fn ($q) => $q->where('created_at', '>=', $corte))
            ->get()
            ->map(function ($d) use ($grupos) {
                $faseValor = ($d->status === 'CONCLUIDA') ? 'CONCLUIDO' : ($d->meta['fase'] ?? null);

                return $this->linhaProcesso(
                    'demanda', $d->id, $d->venda_id, $d->venda->nome_contrato ?? '—',
                    $d->tipo, $d->titular->nome ?? null, $d->status,
                    $d->responsavel_id, $d->responsavel->name ?? null,
                    $d->getRawOriginal('created_at'),
                    $faseValor,
                    $faseValor ? (FaseCancelamento::tryFrom($faseValor)?->label() ?? $faseValor) : null,
                    $grupos[$d->tipo] ?? 'outros',
                    $d->venda?->tabulacao_id,
                    $d->venda ? $d->venda->getRawOriginal('data_implantacao') : null,
                    $d->venda?->tabulacao?->descricao,
                );
            });

        $portabilidades = ! in_array('portabilidade', $gruposPainel, true)
            ? collect()
            : VendaPortabilidade::with([
                'venda:id,nome_contrato,empresa_id,tabulacao_id,data_implantacao',
                'venda.tabulacao:id,descricao',
                'responsavel:id,name',
            ])
                ->where('status', 'PENDENTE')
                ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
                ->whereDoesntHave('venda', fn ($q) => $q->whereIn('tabulacao_id', [Tabulations::ESTORNO, Tabulations::DECLINIO]))
                ->when($corte, fn ($q) => $q->where('vendas_portabilidades.created_at', '>=', $corte))
                ->get()
                ->map(fn ($p) => $this->linhaProcesso(
                    'portabilidade', $p->id, $p->venda_id, $p->venda->nome_contrato ?? '—',
                    'PORTABILIDADE', $p->nome, $p->status,
                    $p->responsavel_id, $p->responsavel->name ?? null,
                    $p->getRawOriginal('created_at'),
                    $p->fase,
                    $p->fase ? (FasePortabilidade::tryFrom($p->fase)?->label() ?? $p->fase) : null,
                    'portabilidade',
                    $p->venda?->tabulacao_id,
                    $p->venda ? $p->venda->getRawOriginal('data_implantacao') : null,
                    $p->venda?->tabulacao?->descricao,
                ));

        $fila = $demandas->concat($portabilidades);

        // Filtros
        if (! empty($filtros['grupo'])) {
            $fila = $fila->where('grupo', $filtros['grupo']);
        }
        if (! empty($filtros['tipo'])) {
            $fila = $fila->where('tipo', $filtros['tipo']);
        }
        if (array_key_exists('responsavel_id', $filtros) && $filtros['responsavel_id'] !== null && $filtros['responsavel_id'] !== '') {
            $fila = $filtros['responsavel_id'] === 'sem'
                ? $fila->whereNull('responsavel_id')
                : $fila->where('responsavel_id', (int) $filtros['responsavel_id']);
        }
        if (! empty($filtros['so_atrasados'])) {
            $fila = $fila->where('atrasado', true);
        }
        if (! empty($filtros['busca'])) {
            $termo = mb_strtolower($filtros['busca']);
            $fila = $fila->filter(fn ($r) => str_contains(mb_strtolower($r['contrato']), $termo)
                || str_contains(mb_strtolower((string) $r['quem']), $termo));
        }

        // Um registro por contrato+tipo: cancelamento/portabilidade criados por
        // titular viram uma linha só (o painel é por contrato). Representa pelo
        // processo mais urgente do grupo e guarda a contagem (qtd).
        $fila = $fila
            ->groupBy(fn ($r) => $r['fonte'].'|'.$r['venda_id'].'|'.$r['tipo'])
            ->map(function ($grupo) {
                $rep = $grupo->sortBy([['ordem_urg', 'asc'], ['ordem_venc', 'asc']])->first();
                $rep['qtd'] = $grupo->count();

                return $rep;
            })
            ->values();

        // Atrasados primeiro (maior atraso), depois vencimento mais próximo.
        return $fila
            ->sortBy([['atrasado', 'desc'], ['dias_atraso', 'desc'], ['ordem_venc', 'asc']])
            ->values()
            ->all();
    }

    public function concluidosNoMes(int $empresaId): int
    {
        [$ini, $fim] = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];

        $grupos = TipoDemandaContrato::grupos();
        $gruposPainel = config('processos.grupos_painel', ['cancelamentos', 'portabilidade']);
        $tiposPainel = array_keys(array_filter($grupos, fn ($g) => in_array($g, $gruposPainel, true)));

        $demandas = VendaDemanda::where('empresa_id', $empresaId)
            ->where('status', 'CONCLUIDA')
            ->whereIn('tipo', $tiposPainel)
            ->whereBetween('concluida_em', [$ini, $fim])
            ->count();

        $portab = ! in_array('portabilidade', $gruposPainel, true) ? 0
            : VendaPortabilidade::where('status', 'CONCLUIDA')
                ->whereBetween('concluida_em', [$ini, $fim])
                ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
                ->count();

        return $demandas + $portab;
    }

    public function concluidosDoMesLista(int $empresaId): array
    {
        [$ini, $fim] = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];

        $grupos = TipoDemandaContrato::grupos();
        $gruposPainel = config('processos.grupos_painel', ['cancelamentos', 'portabilidade']);
        $tiposPainel = array_keys(array_filter($grupos, fn ($g) => in_array($g, $gruposPainel, true)));

        $demandas = VendaDemanda::with(['venda:id,nome_contrato', 'titular:id,nome', 'concluidaPor:id,name'])
            ->where('empresa_id', $empresaId)
            ->where('status', 'CONCLUIDA')
            ->whereIn('tipo', $tiposPainel)
            ->whereBetween('concluida_em', [$ini, $fim])
            ->get()
            ->map(fn ($d) => [
                'venda_id' => $d->venda_id,
                'contrato' => $d->venda->nome_contrato ?? '—',
                'grupo' => $grupos[$d->tipo] ?? 'outros',
                'tipo_label' => TipoDemandaContrato::tryFrom($d->tipo)?->label() ?? $d->tipo,
                'quem' => $d->titular->nome ?? null,
                'desfecho' => 'Concluído',
                'concluido_em' => optional($d->concluida_em)->format('d/m/Y H:i'),
                'por' => $d->concluidaPor->name ?? null,
                'ts' => optional($d->concluida_em)->timestamp ?? 0,
            ]);

        $portab = ! in_array('portabilidade', $gruposPainel, true) ? collect()
            : VendaPortabilidade::with(['venda:id,nome_contrato,empresa_id', 'concluidaPor:id,name'])
                ->where('status', 'CONCLUIDA')
                ->whereBetween('concluida_em', [$ini, $fim])
                ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
                ->get()
                ->map(fn ($p) => [
                    'venda_id' => $p->venda_id,
                    'contrato' => $p->venda->nome_contrato ?? '—',
                    'grupo' => 'portabilidade',
                    'tipo_label' => 'Portabilidade',
                    'quem' => $p->nome,
                    'desfecho' => $p->fase === 'NEGADA' ? 'Negada' : 'Concluída',
                    'concluido_em' => optional($p->concluida_em)->format('d/m/Y H:i'),
                    'por' => $p->concluidaPor->name ?? null,
                    'ts' => optional($p->concluida_em)->timestamp ?? 0,
                ]);

        return $demandas->concat($portab)->sortByDesc('ts')->values()->all();
    }

    /**
     * Atribui responsável a TODOS os processos pendentes do mesmo contrato+tipo do
     * processo informado (o painel agrupa por contrato). $fonte = 'demanda'|'portabilidade'.
     */
    public function atribuirResponsavelDoContrato(string $fonte, int $id, int $empresaId, ?int $responsavelId): bool
    {
        if ($fonte === 'portabilidade') {
            $base = VendaPortabilidade::where('id', $id)
                ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))->first();
            if (! $base) {
                return false;
            }
            VendaPortabilidade::where('venda_id', $base->venda_id)->where('status', 'PENDENTE')
                ->update(['responsavel_id' => $responsavelId]);

            return true;
        }

        $base = VendaDemanda::where('id', $id)->where('empresa_id', $empresaId)->first();
        if (! $base) {
            return false;
        }
        VendaDemanda::where('empresa_id', $empresaId)->where('venda_id', $base->venda_id)
            ->where('tipo', $base->tipo)->where('status', 'PENDENTE')
            ->update(['responsavel_id' => $responsavelId]);

        return true;
    }

    /** Avança a fase de TODOS os cancelamentos pendentes do mesmo contrato+tipo (fase final conclui). */
    public function avancarFaseCancelamentoDoContrato(int $id, int $empresaId, string $fase, int $userId): bool
    {
        $base = VendaDemanda::where('id', $id)->where('empresa_id', $empresaId)->first();
        if (! $base) {
            return false;
        }

        $ehFinal = $fase === FaseCancelamento::CONCLUIDO->value;
        $irmas = VendaDemanda::where('empresa_id', $empresaId)->where('venda_id', $base->venda_id)
            ->where('tipo', $base->tipo)->where('status', 'PENDENTE')->get();

        foreach ($irmas as $d) {
            $meta = $d->meta ?? [];
            $meta['fase'] = $fase;
            $d->update([
                'meta' => $meta,
                'status' => $ehFinal ? 'CONCLUIDA' : 'PENDENTE',
                'concluida_por' => $ehFinal ? $userId : null,
                'concluida_em' => $ehFinal ? Carbon::now() : null,
            ]);
        }

        return true;
    }

    /** Avança a fase de TODAS as portabilidades pendentes do mesmo contrato (fase final conclui). */
    public function avancarFasePortabilidadeDoContrato(int $id, int $empresaId, string $fase, int $userId): bool
    {
        $base = VendaPortabilidade::where('id', $id)
            ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))->first();
        if (! $base) {
            return false;
        }

        $ehFinal = FasePortabilidade::tryFrom($fase)?->ehFinal() ?? false;
        VendaPortabilidade::where('venda_id', $base->venda_id)->where('status', 'PENDENTE')
            ->update([
                'fase' => $fase,
                'status' => $ehFinal ? 'CONCLUIDA' : 'PENDENTE',
                'concluida_em' => $ehFinal ? Carbon::now() : null,
                'concluida_por' => $ehFinal ? $userId : null,
            ]);

        return true;
    }

    public function atribuirResponsavel(string $fonte, int $id, int $empresaId, ?int $responsavelId): bool
    {
        if ($fonte === 'portabilidade') {
            return (bool) VendaPortabilidade::where('id', $id)
                ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
                ->update(['responsavel_id' => $responsavelId]);
        }

        return (bool) VendaDemanda::where('id', $id)->where('empresa_id', $empresaId)
            ->update(['responsavel_id' => $responsavelId]);
    }

    public function concluirProcesso(string $fonte, int $id, int $empresaId, int $userId): bool
    {
        $dados = ['status' => 'CONCLUIDA', 'concluida_em' => Carbon::now(), 'concluida_por' => $userId];

        if ($fonte === 'portabilidade') {
            return (bool) VendaPortabilidade::where('id', $id)
                ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
                ->update($dados);
        }

        return (bool) VendaDemanda::where('id', $id)->where('empresa_id', $empresaId)->update($dados);
    }

    public function emailsCriadosDaVenda(int $vendaId, int $empresaId): array
    {
        return \App\Models\VendaEmailCriado::with(['titular:id,nome', 'criador:id,name'])
            ->where('venda_id', $vendaId)
            ->where('empresa_id', $empresaId)
            ->orderBy('id')
            ->get()
            ->map(fn ($e) => $this->mapEmailCriado($e))
            ->all();
    }

    public function criarEmailCriado(int $vendaId, int $empresaId, array $dados, int $userId): array
    {
        $email = \App\Models\VendaEmailCriado::create([
            'venda_id' => $vendaId,
            'empresa_id' => $empresaId,
            'titular_id' => $dados['titular_id'] ?? null,
            'email' => trim($dados['email']),
            'senha' => $dados['senha'],
            'observacao' => $dados['observacao'] ?? null,
            'created_by' => $userId,
        ]);

        return $this->mapEmailCriado($email->load(['titular:id,nome', 'criador:id,name']));
    }

    public function atualizarEmailCriado(int $id, int $empresaId, array $dados): ?array
    {
        $email = \App\Models\VendaEmailCriado::where('id', $id)->where('empresa_id', $empresaId)->first();

        if (! $email) {
            return null;
        }

        $email->update([
            'titular_id' => $dados['titular_id'] ?? null,
            'email' => trim($dados['email']),
            'senha' => $dados['senha'],
            'observacao' => $dados['observacao'] ?? null,
        ]);

        return $this->mapEmailCriado($email->fresh(['titular:id,nome', 'criador:id,name']));
    }

    public function excluirEmailCriado(int $id, int $empresaId): bool
    {
        return (bool) \App\Models\VendaEmailCriado::where('id', $id)->where('empresa_id', $empresaId)->delete();
    }

    private function mapEmailCriado(\App\Models\VendaEmailCriado $e): array
    {
        return [
            'id' => $e->id,
            'venda_id' => $e->venda_id,
            'titular_id' => $e->titular_id,
            'titular' => $e->titular->nome ?? null,
            'email' => $e->email,
            'senha' => $e->senha,
            'observacao' => $e->observacao,
            'criado_por' => $e->criador->name ?? null,
            'criado_em' => $e->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y'),
        ];
    }

    public function buscarContratos(string $termo, int $empresaId, int $limite = 20): array
    {
        $digitos = preg_replace('/\D+/', '', $termo);

        return Vendas::with('tabulacao:id,descricao')
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($termo, $digitos) {
                $q->where('nome_contrato', 'like', "%{$termo}%")
                    ->orWhere('numero_proposta', 'like', "%{$termo}%");
                if ($digitos !== '') {
                    $q->orWhere('cpf_cnpj', 'like', "%{$digitos}%");
                }
            })
            ->orderByDesc('id')
            ->limit($limite)
            ->get(['id', 'nome_contrato', 'cpf_cnpj', 'operadora', 'numero_proposta', 'tabulacao_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'cpf_cnpj' => $v->cpf_cnpj,
                'operadora' => $v->operadora,
                'numero_proposta' => $v->numero_proposta,
                'status' => $v->tabulacao->descricao ?? '—',
            ])
            ->all();
    }

    public function contratosDoCnpj(string $cnpj, int $empresaId, int $exceptVendaId): array
    {
        if ($cnpj === '') {
            return [];
        }

        return Vendas::with('tabulacao:id,descricao')
            ->where('empresa_id', $empresaId)
            ->where('cpf_cnpj', $cnpj)
            ->where('id', '!=', $exceptVendaId)
            ->orderByDesc('data_vigencia')
            ->orderByDesc('id')
            ->get(['id', 'nome_contrato', 'operadora', 'nome_plano', 'vidas', 'valor_contrato', 'data_vigencia', 'data_implantacao', 'tabulacao_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'operadora' => $v->operadora,
                'nome_plano' => $v->nome_plano,
                'vidas' => $v->vidas,
                'valor_contrato' => $v->valor_contrato !== null
                    ? 'R$ '.number_format((float) $v->valor_contrato, 2, ',', '.')
                    : null,
                'status' => $v->tabulacao->descricao ?? '—',
                'data_vigencia' => $v->getRawOriginal('data_vigencia') ? Carbon::parse($v->getRawOriginal('data_vigencia'))->format('d/m/Y') : null,
                'data_implantacao' => $v->getRawOriginal('data_implantacao') ? Carbon::parse($v->getRawOriginal('data_implantacao'))->format('d/m/Y') : null,
            ])
            ->all();
    }

    public function acessosPorCnpj(string $cnpj, int $empresaId, int $vendaId): array
    {
        return CredencialAcesso::with('operadora:id,nome')
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($cnpj, $vendaId) {
                $q->where('venda_id', $vendaId);
                if ($cnpj !== '') {
                    $q->orWhere('cnpj', $cnpj)
                        ->orWhereRaw("REGEXP_REPLACE(login, '[^0-9]', '') = ?", [$cnpj]);
                }
            })
            ->orderBy('nome')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'tipo' => $c->tipo,
                'nome' => $c->nome,
                'login' => $c->login,
                'senha' => $c->senha,
                'operadora' => $c->operadora->nome ?? null,
                'observacao' => $c->observacao,
            ])
            ->all();
    }

    public function atualizarFasePortabilidade(int $id, int $empresaId, string $fase, int $userId): bool
    {
        $port = VendaPortabilidade::where('id', $id)
            ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
            ->first();

        if (! $port) {
            return false;
        }

        $ehFinal = FasePortabilidade::tryFrom($fase)?->ehFinal() ?? false;

        $port->update([
            'fase' => $fase,
            'status' => $ehFinal ? 'CONCLUIDA' : 'PENDENTE',
            'concluida_em' => $ehFinal ? Carbon::now() : null,
            'concluida_por' => $ehFinal ? $userId : null,
        ]);

        return true;
    }

    /**
     * Normaliza uma linha da fila e calcula prazo/urgência. O relógio do prazo só
     * começa na IMPLANTAÇÃO do contrato (a operação depende dela); antes disso o
     * processo fica "aguardando implantação" e não conta atraso.
     */
    private function linhaProcesso(
        string $fonte, int $id, ?int $vendaId, string $contrato, string $tipo, ?string $quem,
        string $status, ?int $responsavelId, ?string $responsavel, $createdAt, ?string $faseValor, ?string $faseLabel, string $grupo,
        ?int $tabulacaoId = null, $dataImplantacao = null, ?string $situacaoContrato = null,
    ): array {
        $sla = config('processos.sla_dias', []);
        $dias = $sla[$tipo] ?? ($sla['default'] ?? 15);
        $alerta = (int) config('processos.alerta_dias', 7);

        $abertura = Carbon::parse($createdAt);
        $hoje = Carbon::now();

        // Fonte da verdade da implantação: tabulação IMPLANTADO ou data preenchida.
        $implantado = $tabulacaoId === Tabulations::IMPLANTADO || ! empty($dataImplantacao);

        // Caso raro "portou/cancelou antes de implantar": se a fase já avançou além
        // da primeira, trata como em andamento (não bloqueia).
        $emAndamento = $faseValor !== null && ! in_array($faseValor, ['SOLICITADO', 'REUNINDO_DOCUMENTOS'], true);

        // Início do relógio: data de implantação; se implantado sem data (legado) ou
        // já em andamento, cai para a abertura. Sem nada disso, fica bloqueado.
        $clockStart = null;
        if (! empty($dataImplantacao)) {
            $clockStart = Carbon::parse($dataImplantacao);
        } elseif ($implantado || $emAndamento) {
            $clockStart = $abertura;
        }

        $bloqueado = $clockStart === null;
        $vencimento = $bloqueado ? null : $clockStart->copy()->addDays($dias);
        $atrasado = $vencimento !== null && $hoje->greaterThan($vencimento);
        $diasParaVencer = $vencimento !== null ? (int) $hoje->diffInDays($vencimento, false) : null;
        $vencendo = $vencimento !== null && ! $atrasado && $diasParaVencer <= $alerta;

        $urgencia = $bloqueado ? 'aguardando_implantacao'
            : ($atrasado ? 'atrasado' : ($vencendo ? 'vencendo' : 'em_dia'));
        $ordemUrg = ['atrasado' => 0, 'vencendo' => 1, 'em_dia' => 2, 'aguardando_implantacao' => 3][$urgencia] ?? 9;

        return [
            'fonte' => $fonte,
            'id' => $id,
            'venda_id' => $vendaId,
            'qtd' => 1,
            'ordem_urg' => $ordemUrg,
            'contrato' => $contrato,
            'tipo' => $tipo,
            'tipo_label' => TipoDemandaContrato::tryFrom($tipo)?->label() ?? $tipo,
            'grupo' => $grupo,
            'quem' => $quem,
            'fase' => $faseLabel,
            'fase_valor' => $faseValor,
            'status' => $status,
            'responsavel_id' => $responsavelId,
            'responsavel' => $responsavel,
            'aberto_em' => $abertura->format('d/m/Y'),
            'dias_aberto' => (int) $abertura->diffInDays($hoje),
            // Situação do contrato-pai e estado do relógio de prazo.
            'implantado' => $implantado,
            'bloqueado' => $bloqueado,
            'urgencia' => $urgencia,
            'situacao_contrato' => $situacaoContrato ?? '—',
            'data_implantacao' => ! empty($dataImplantacao) ? Carbon::parse($dataImplantacao)->format('d/m/Y') : null,
            'dias_bloqueado' => $bloqueado ? (int) $abertura->diffInDays($hoje) : 0,
            'vence_em' => $vencimento?->format('d/m/Y'),
            'dias_para_vencer' => $diasParaVencer,
            'ordem_venc' => $vencimento ? $vencimento->timestamp : PHP_INT_MAX,
            'atrasado' => $atrasado,
            'dias_atraso' => $atrasado ? (int) $vencimento->diffInDays($hoje) : 0,
        ];
    }
}
