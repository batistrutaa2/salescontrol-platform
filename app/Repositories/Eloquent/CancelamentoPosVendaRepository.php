<?php

namespace App\Repositories\Eloquent;

use App\Enums\EtapaCancelamento;
use App\Enums\Tabulations;
use App\Enums\ViaCancelamento;
use App\Models\CancelamentoPosVenda;
use App\Models\CancelamentoPosVendaHistorico;
use App\Models\Vendas;
use App\Repositories\Contracts\CancelamentoPosVendaRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancelamentoPosVendaRepository implements CancelamentoPosVendaRepositoryInterface
{
    public function listar(int $empresaId): array
    {
        return CancelamentoPosVenda::with([
            'venda:id,nome_contrato,cpf_cnpj,operadora,tabulacao_id,data_implantacao',
            'responsavel:id,name',
        ])
            ->where('empresa_id', $empresaId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (CancelamentoPosVenda $c) => $this->linha($c))
            ->all();
    }

    public function kpis(int $empresaId): array
    {
        $contagem = CancelamentoPosVenda::where('empresa_id', $empresaId)
            ->select('etapa', DB::raw('COUNT(*) as total'))
            ->groupBy('etapa')
            ->pluck('total', 'etapa');

        $kpis = [];
        foreach (EtapaCancelamento::cases() as $etapa) {
            $kpis[$etapa->value] = (int) ($contagem[$etapa->value] ?? 0);
        }

        return $kpis;
    }

    public function criar(int $empresaId, array $dados, int $userId): ?CancelamentoPosVenda
    {
        $venda = Vendas::where('id', $dados['venda_id'])
            ->where('empresa_id', $empresaId)
            ->first();

        if (! $venda) {
            return null;
        }

        $etapaInicial = $this->vendaImplantada($venda)
            ? EtapaCancelamento::PRONTO_PARA_SOLICITAR
            : EtapaCancelamento::AGUARDANDO_IMPLANTACAO;

        return DB::transaction(function () use ($empresaId, $dados, $userId, $etapaInicial) {
            $cancelamento = CancelamentoPosVenda::create([
                'venda_id' => $dados['venda_id'],
                'empresa_id' => $empresaId,
                'escopo' => $dados['escopo'],
                'beneficiario_nome' => $dados['escopo'] === 'BENEFICIARIO' ? ($dados['beneficiario_nome'] ?? null) : null,
                'via' => $dados['via'],
                'operadora_anterior' => $dados['operadora_anterior'],
                'etapa' => $etapaInicial->value,
                'observacoes' => $dados['observacoes'] ?? null,
                'responsavel_id' => $dados['responsavel_id'] ?? null,
                'created_by' => $userId,
            ]);

            CancelamentoPosVendaHistorico::create([
                'cancelamento_pos_venda_id' => $cancelamento->id,
                'user_id' => $userId,
                'campo_alterado' => 'etapa',
                'valor_anterior' => null,
                'valor_novo' => $etapaInicial->label(),
                'observacao' => 'Cancelamento aberto.',
            ]);

            return $cancelamento;
        });
    }

    public function moverEtapa(int $id, int $empresaId, string $etapa, int $userId, ?string $protocolo = null, ?string $observacao = null): bool
    {
        $cancelamento = CancelamentoPosVenda::where('empresa_id', $empresaId)->find($id);
        if (! $cancelamento) {
            return false;
        }

        $nova = EtapaCancelamento::from($etapa);
        $atual = EtapaCancelamento::from($cancelamento->etapa);

        if ($nova === $atual) {
            return true;
        }

        DB::transaction(function () use ($cancelamento, $nova, $atual, $userId, $protocolo, $observacao) {
            CancelamentoPosVendaHistorico::create([
                'cancelamento_pos_venda_id' => $cancelamento->id,
                'user_id' => $userId,
                'campo_alterado' => 'etapa',
                'valor_anterior' => $atual->label(),
                'valor_novo' => $nova->label(),
                'observacao' => $observacao,
            ]);

            $cancelamento->etapa = $nova->value;

            if ($nova === EtapaCancelamento::SOLICITADO) {
                $cancelamento->solicitado_em = $cancelamento->solicitado_em ?? now();
                if ($protocolo !== null && $protocolo !== '') {
                    $cancelamento->protocolo = $protocolo;
                }
            }
            if ($nova === EtapaCancelamento::CONCLUIDO) {
                $cancelamento->concluido_em = now();
            }

            $cancelamento->save();
        });

        return true;
    }

    public function atribuirResponsavel(int $id, int $empresaId, ?int $responsavelId, int $userId): bool
    {
        $cancelamento = CancelamentoPosVenda::with('responsavel:id,name')
            ->where('empresa_id', $empresaId)
            ->find($id);
        if (! $cancelamento) {
            return false;
        }

        if ((int) $cancelamento->responsavel_id === (int) $responsavelId) {
            return true;
        }

        $nomeAnterior = $cancelamento->responsavel?->name;

        DB::transaction(function () use ($cancelamento, $responsavelId, $userId, $nomeAnterior) {
            $cancelamento->responsavel_id = $responsavelId;
            $cancelamento->save();

            CancelamentoPosVendaHistorico::create([
                'cancelamento_pos_venda_id' => $cancelamento->id,
                'user_id' => $userId,
                'campo_alterado' => 'responsavel',
                'valor_anterior' => $nomeAnterior,
                'valor_novo' => $cancelamento->fresh('responsavel')->responsavel?->name ?? 'Sem responsável',
            ]);
        });

        return true;
    }

    public function desistir(int $id, int $empresaId, int $userId, ?string $motivo): bool
    {
        return $this->moverEtapa(
            $id,
            $empresaId,
            EtapaCancelamento::DESISTIDO->value,
            $userId,
            observacao: $motivo !== null && $motivo !== '' ? "Motivo: {$motivo}" : null,
        );
    }

    public function atualizar(int $id, int $empresaId, array $dados, int $userId): bool
    {
        $cancelamento = CancelamentoPosVenda::where('empresa_id', $empresaId)->find($id);
        if (! $cancelamento) {
            return false;
        }

        $auditaveis = ['protocolo' => 'protocolo', 'observacoes' => 'observacoes'];

        DB::transaction(function () use ($cancelamento, $dados, $userId, $auditaveis) {
            foreach ($auditaveis as $campo => $label) {
                if (! array_key_exists($campo, $dados) || $dados[$campo] === $cancelamento->$campo) {
                    continue;
                }

                CancelamentoPosVendaHistorico::create([
                    'cancelamento_pos_venda_id' => $cancelamento->id,
                    'user_id' => $userId,
                    'campo_alterado' => $label,
                    'valor_anterior' => $cancelamento->$campo !== null ? mb_substr((string) $cancelamento->$campo, 0, 255) : null,
                    'valor_novo' => $dados[$campo] !== null ? mb_substr((string) $dados[$campo], 0, 255) : null,
                ]);

                $cancelamento->$campo = $dados[$campo];
            }

            $cancelamento->save();
        });

        return true;
    }

    public function detalhe(int $id, int $empresaId): ?array
    {
        $cancelamento = CancelamentoPosVenda::with([
            'venda:id,nome_contrato,cpf_cnpj,operadora,nome_plano,numero_proposta,tabulacao_id,data_implantacao',
            'responsavel:id,name',
            'criador:id,name',
            'historico.usuario:id,name',
        ])
            ->where('empresa_id', $empresaId)
            ->find($id);

        if (! $cancelamento) {
            return null;
        }

        $etapa = EtapaCancelamento::from($cancelamento->etapa);

        return [
            'registro' => $this->linha($cancelamento) + [
                'nome_plano' => $cancelamento->venda?->nome_plano,
                'numero_proposta' => $cancelamento->venda?->numero_proposta,
                'observacoes' => $cancelamento->observacoes,
                'criado_por_nome' => $cancelamento->criador?->name,
                'etapa_label' => $etapa->label(),
            ],
            'historico' => $cancelamento->historico->map(fn (CancelamentoPosVendaHistorico $h) => [
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

    public function buscarContratos(string $termo, int $empresaId, int $limite = 20): array
    {
        $termo = trim(preg_replace('/\s+/', ' ', $termo));
        if (mb_strlen($termo) < 2) {
            return [];
        }

        $like = '%'.$this->escaparLike($termo).'%';
        $digitos = preg_replace('/\D+/', '', $termo);

        return Vendas::where('empresa_id', $empresaId)
            ->where(function ($q) use ($like, $digitos) {
                $q->where('nome_contrato', 'like', $like)
                    ->orWhere('numero_proposta', 'like', $like)
                    ->orWhere('cpf_cnpj', 'like', $like);
                if ($digitos !== '') {
                    $q->orWhere('cpf_cnpj', 'like', "%{$digitos}%");
                }
            })
            ->orderByDesc('id')
            ->limit($limite)
            ->get(['id', 'nome_contrato', 'cpf_cnpj', 'numero_proposta', 'operadora', 'tabulacao_id', 'data_implantacao'])
            ->map(fn (Vendas $v) => [
                'id' => $v->id,
                'nome_contrato' => $v->nome_contrato,
                'cpf_cnpj' => $v->cpf_cnpj,
                'numero_proposta' => $v->numero_proposta,
                'operadora' => $v->operadora,
                'data_implantacao' => $v->data_implantacao?->format('d/m/Y'),
                'implantado' => $this->vendaImplantada($v),
            ])
            ->all();
    }

    // ---------------------------------------------------------------

    /** Uma linha do painel (kanban e tabela compartilham o formato). */
    private function linha(CancelamentoPosVenda $c): array
    {
        $implantado = $c->venda !== null && $this->vendaImplantada($c->venda);

        return [
            'id' => $c->id,
            'venda_id' => $c->venda_id,
            'nome_contrato' => $c->venda?->nome_contrato ?? '—',
            'cpf_cnpj' => $c->venda?->cpf_cnpj,
            'operadora_nova' => $c->venda?->operadora,
            'operadora_anterior' => $c->operadora_anterior,
            'escopo' => $c->escopo,
            'escopo_label' => $c->escopo === 'BENEFICIARIO' ? 'Beneficiário' : 'Apólice inteira',
            'beneficiario_nome' => $c->beneficiario_nome,
            'via' => $c->via,
            'via_label' => ViaCancelamento::tryFrom($c->via)?->label() ?? $c->via,
            'etapa' => $c->etapa,
            'protocolo' => $c->protocolo,
            'responsavel_id' => $c->responsavel_id,
            'responsavel_nome' => $c->responsavel?->name,
            'implantado' => $implantado,
            'data_implantacao' => $c->venda?->data_implantacao?->format('d/m/Y'),
            'solicitado_em' => $c->solicitado_em?->format('d/m/Y'),
            'criado_em' => $c->getRawOriginal('created_at')
                ? Carbon::parse($c->getRawOriginal('created_at'))->setTimezone('America/Sao_Paulo')->format('d/m/Y')
                : '—',
        ];
    }

    private function vendaImplantada(Vendas $venda): bool
    {
        return (int) $venda->tabulacao_id === Tabulations::IMPLANTADO
            || $venda->data_implantacao !== null;
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
