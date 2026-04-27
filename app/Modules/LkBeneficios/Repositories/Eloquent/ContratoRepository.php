<?php

namespace App\Modules\LkBeneficios\Repositories\Eloquent;

use App\Modules\LkBeneficios\Enums\StatusContrato;
use App\Modules\LkBeneficios\Enums\TipoBeneficio;
use App\Modules\LkBeneficios\Enums\TipoMovimentacao;
use App\Modules\LkBeneficios\Models\Contrato;
use App\Modules\LkBeneficios\Models\DetalhesPatrimonial;
use App\Modules\LkBeneficios\Models\DetalhesPrevidencia;
use App\Modules\LkBeneficios\Models\DetalhesVida;
use App\Modules\LkBeneficios\Models\Movimentacao;
use App\Modules\LkBeneficios\Repositories\Contracts\ContratoRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContratoRepository implements ContratoRepositoryInterface
{
    public function queryForDataTable(int $empresaId, array $filtros = []): Builder
    {
        $query = Contrato::query()
            ->with(['produto', 'user:id,name'])
            ->where('empresa_id', $empresaId);

        if (! empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }
        if (! empty($filtros['tipo'])) {
            $query->whereHas('produto', fn ($q) => $q->where('tipo', $filtros['tipo']));
        }
        if (! empty($filtros['busca'])) {
            $busca = '%' . $filtros['busca'] . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome_cliente', 'like', $busca)
                    ->orWhere('cpf_cnpj', 'like', $busca)
                    ->orWhere('numero_apolice', 'like', $busca)
                    ->orWhere('numero_proposta', 'like', $busca);
            });
        }

        return $query;
    }

    public function findByEmpresa(int $id, int $empresaId): ?Contrato
    {
        return Contrato::with([
            'produto', 'user:id,name', 'beneficiarios', 'apolices', 'parcelas',
            'movimentacoes.user:id,name',
            'detalhesVida', 'detalhesPrevidencia', 'detalhesPatrimonial',
            'vendaOrigem:id,nome_contrato,operadora,nome_plano',
        ])
            ->where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();
    }

    public function create(array $data): Contrato
    {
        return DB::transaction(function () use ($data) {
            $contrato = Contrato::create([
                'empresa_id' => $data['empresa_id'] ?? Auth::user()->empresa_id,
                'user_id' => $data['user_id'] ?? Auth::id(),
                'produto_id' => $data['produto_id'],
                'venda_origem_id' => $data['venda_origem_id'] ?? null,
                'lead_id' => $data['lead_id'] ?? null,
                'pessoa_id' => $data['pessoa_id'] ?? null,
                'empresa_lemit_id' => $data['empresa_lemit_id'] ?? null,
                'numero_apolice' => $data['numero_apolice'] ?? null,
                'numero_proposta' => $data['numero_proposta'] ?? null,
                'cliente_tipo' => $data['cliente_tipo'],
                'cpf_cnpj' => preg_replace('/\D+/', '', $data['cpf_cnpj']),
                'nome_cliente' => $data['nome_cliente'],
                'status' => $data['status'] ?? StatusContrato::EM_IMPLANTACAO,
                'data_proposta' => $data['data_proposta'] ?? null,
                'data_vigencia_inicio' => $data['data_vigencia_inicio'] ?? null,
                'data_vigencia_fim' => $data['data_vigencia_fim'] ?? null,
                'data_aniversario_reajuste' => $data['data_aniversario_reajuste'] ?? null,
                'valor_mensal' => $data['valor_mensal'] ?? 0,
                'valor_anual' => $data['valor_anual'] ?? ($data['valor_mensal'] ?? 0) * 12,
                'forma_pagamento' => $data['forma_pagamento'] ?? 'MENSAL',
                'dia_vencimento' => $data['dia_vencimento'] ?? null,
                'vidas_total' => $data['vidas_total'] ?? 1,
                'vidas_titulares' => $data['vidas_titulares'] ?? 1,
                'vidas_dependentes' => $data['vidas_dependentes'] ?? 0,
                'comissao_percentual_primeira' => $data['comissao_percentual_primeira'] ?? 0,
                'comissao_percentual_recorrente' => $data['comissao_percentual_recorrente'] ?? 0,
                'observacoes' => $data['observacoes'] ?? null,
            ]);

            $this->persistirDetalhesPorTipo($contrato, $data);

            Movimentacao::create([
                'contrato_id' => $contrato->id,
                'user_id' => Auth::id(),
                'tipo' => TipoMovimentacao::INCLUSAO,
                'descricao' => 'Contrato criado',
                'valor_novo' => $contrato->valor_mensal,
            ]);

            return $contrato->fresh([
                'produto', 'detalhesVida', 'detalhesPrevidencia', 'detalhesPatrimonial',
            ]);
        });
    }

    public function update(int $id, int $empresaId, array $data): Contrato
    {
        return DB::transaction(function () use ($id, $empresaId, $data) {
            $contrato = Contrato::where('id', $id)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();

            $valorAnterior = $contrato->valor_mensal;

            $camposAtualizaveis = [
                'produto_id', 'numero_apolice', 'numero_proposta', 'cliente_tipo',
                'nome_cliente', 'status', 'data_proposta', 'data_vigencia_inicio',
                'data_vigencia_fim', 'data_aniversario_reajuste', 'valor_mensal',
                'valor_anual', 'forma_pagamento', 'dia_vencimento', 'vidas_total',
                'vidas_titulares', 'vidas_dependentes', 'comissao_percentual_primeira',
                'comissao_percentual_recorrente', 'observacoes',
            ];
            $payload = array_intersect_key($data, array_flip($camposAtualizaveis));

            if (isset($payload['cpf_cnpj'])) {
                $payload['cpf_cnpj'] = preg_replace('/\D+/', '', $payload['cpf_cnpj']);
            }

            $contrato->update($payload);

            $this->persistirDetalhesPorTipo($contrato, $data);

            if (isset($payload['valor_mensal']) && (float) $payload['valor_mensal'] !== (float) $valorAnterior) {
                Movimentacao::create([
                    'contrato_id' => $contrato->id,
                    'user_id' => Auth::id(),
                    'tipo' => TipoMovimentacao::REAJUSTE,
                    'descricao' => 'Valor mensal alterado',
                    'valor_anterior' => $valorAnterior,
                    'valor_novo' => $payload['valor_mensal'],
                ]);
            }

            return $contrato->fresh([
                'produto', 'detalhesVida', 'detalhesPrevidencia', 'detalhesPatrimonial',
            ]);
        });
    }

    public function delete(int $id, int $empresaId): bool
    {
        $contrato = Contrato::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        return (bool) $contrato->delete();
    }

    public function countByStatus(int $empresaId): array
    {
        $rows = Contrato::where('empresa_id', $empresaId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $base = array_fill_keys(StatusContrato::all(), 0);
        return array_merge($base, $rows);
    }

    public function somaMrr(int $empresaId): float
    {
        return (float) Contrato::where('empresa_id', $empresaId)
            ->where('status', StatusContrato::ATIVO)
            ->sum('valor_mensal');
    }

    public function reajustesProximos(int $empresaId, int $dias = 30)
    {
        $hoje = Carbon::today();
        $limite = $hoje->copy()->addDays($dias);

        return Contrato::where('empresa_id', $empresaId)
            ->where('status', StatusContrato::ATIVO)
            ->whereNotNull('data_aniversario_reajuste')
            ->whereRaw('DATE_FORMAT(data_aniversario_reajuste, "%m-%d") BETWEEN ? AND ?', [
                $hoje->format('m-d'),
                $limite->format('m-d'),
            ])
            ->orderBy('data_aniversario_reajuste')
            ->with('produto:id,nome,tipo')
            ->get();
    }

    private function persistirDetalhesPorTipo(Contrato $contrato, array $data): void
    {
        $tipo = $contrato->produto?->tipo ?? $contrato->produto()->first()?->tipo;
        if (! $tipo) {
            return;
        }

        match ($tipo) {
            TipoBeneficio::VIDA => $this->upsertVida($contrato->id, $data['detalhes_vida'] ?? null),
            TipoBeneficio::PREVIDENCIA => $this->upsertPrevidencia($contrato->id, $data['detalhes_previdencia'] ?? null),
            TipoBeneficio::PATRIMONIAL => $this->upsertPatrimonial($contrato->id, $data['detalhes_patrimonial'] ?? null),
            default => null,
        };
    }

    private function upsertVida(int $contratoId, ?array $d): void
    {
        if (! $d) {
            return;
        }
        DetalhesVida::updateOrCreate(
            ['contrato_id' => $contratoId],
            [
                'capital_segurado' => $d['capital_segurado'] ?? null,
                'cobertura_morte' => $d['cobertura_morte'] ?? null,
                'cobertura_invalidez' => $d['cobertura_invalidez'] ?? null,
                'cobertura_doencas_graves' => $d['cobertura_doencas_graves'] ?? null,
                'possui_assistencia_funeral' => (bool) ($d['possui_assistencia_funeral'] ?? false),
                'beneficiarios_designados' => $d['beneficiarios_designados'] ?? null,
            ]
        );
    }

    private function upsertPrevidencia(int $contratoId, ?array $d): void
    {
        if (! $d) {
            return;
        }
        DetalhesPrevidencia::updateOrCreate(
            ['contrato_id' => $contratoId],
            [
                'modalidade' => $d['modalidade'] ?? 'VGBL',
                'tabela_imposto' => $d['tabela_imposto'] ?? 'PROGRESSIVA',
                'aporte_mensal' => $d['aporte_mensal'] ?? 0,
                'aporte_inicial' => $d['aporte_inicial'] ?? 0,
                'rentabilidade_acumulada' => $d['rentabilidade_acumulada'] ?? 0,
                'data_ultimo_aporte' => $d['data_ultimo_aporte'] ?? null,
            ]
        );
    }

    private function upsertPatrimonial(int $contratoId, ?array $d): void
    {
        if (! $d) {
            return;
        }
        DetalhesPatrimonial::updateOrCreate(
            ['contrato_id' => $contratoId],
            [
                'tipo_bem' => $d['tipo_bem'] ?? 'RESIDENCIAL',
                'descricao_bem' => $d['descricao_bem'] ?? null,
                'valor_segurado' => $d['valor_segurado'] ?? 0,
                'franquia' => $d['franquia'] ?? 0,
                'identificador_bem' => $d['identificador_bem'] ?? null,
                'coberturas' => $d['coberturas'] ?? null,
            ]
        );
    }
}
