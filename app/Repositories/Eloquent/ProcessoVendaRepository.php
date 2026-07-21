<?php

namespace App\Repositories\Eloquent;

use App\Enums\FaseCancelamento;
use App\Enums\FasePortabilidade;
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

        $demandas = VendaDemanda::with(['venda:id,nome_contrato', 'titular:id,nome', 'responsavel:id,name'])
            ->where('empresa_id', $empresaId)
            ->where('status', 'PENDENTE')
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
                );
            });

        $portabilidades = VendaPortabilidade::with(['venda:id,nome_contrato,empresa_id', 'responsavel:id,name'])
            ->where('status', 'PENDENTE')
            ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
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

        // Atrasados primeiro (maior atraso), depois vencimento mais próximo.
        return $fila
            ->sortBy([['atrasado', 'desc'], ['dias_atraso', 'desc'], ['ordem_venc', 'asc']])
            ->values()
            ->all();
    }

    public function concluidosNoMes(int $empresaId): int
    {
        [$ini, $fim] = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];

        $demandas = VendaDemanda::where('empresa_id', $empresaId)
            ->where('status', 'CONCLUIDA')
            ->whereBetween('concluida_em', [$ini, $fim])
            ->count();

        $portab = VendaPortabilidade::where('status', 'CONCLUIDA')
            ->whereBetween('concluida_em', [$ini, $fim])
            ->whereHas('venda', fn ($q) => $q->where('empresa_id', $empresaId))
            ->count();

        return $demandas + $portab;
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

    /** Normaliza uma linha da fila e calcula prazo/atraso a partir do SLA do tipo. */
    private function linhaProcesso(
        string $fonte, int $id, ?int $vendaId, string $contrato, string $tipo, ?string $quem,
        string $status, ?int $responsavelId, ?string $responsavel, $createdAt, ?string $faseValor, ?string $faseLabel, string $grupo,
    ): array {
        $sla = config('processos.sla_dias', []);
        $dias = $sla[$tipo] ?? ($sla['default'] ?? 15);

        $abertura = Carbon::parse($createdAt);
        $vencimento = $abertura->copy()->addDays($dias);
        $hoje = Carbon::now();
        $atrasado = $hoje->greaterThan($vencimento);

        return [
            'fonte' => $fonte,
            'id' => $id,
            'venda_id' => $vendaId,
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
            'vence_em' => $vencimento->format('d/m/Y'),
            'ordem_venc' => $vencimento->timestamp,
            'atrasado' => $atrasado,
            'dias_atraso' => $atrasado ? (int) $vencimento->diffInDays($hoje) : 0,
        ];
    }
}
