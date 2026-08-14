<?php

namespace App\Services;

use App\Enums\RenovacaoStatus;
use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\RenovacaoInteracao;
use App\Models\RenovacaoOportunidade;
use App\Models\User;
use App\Models\Vendas;
use App\Notifications\CotacaoRenovacaoSolicitada;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenovacaoService
{
    public static function normalizarDocumento(?string $documento): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $documento);
        return in_array(strlen($digitos), [11, 14], true) ? $digitos : null;
    }

    public function sincronizar(bool $dryRun = false, ?int $empresaId = null): array
    {
        $resultado = ['normalizadas' => 0, 'elegiveis' => 0, 'criadas' => 0, 'atualizadas' => 0, 'suspensas' => 0];

        Vendas::query()->whereNull('cpf_cnpj_normalizado')->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))->select(['id', 'cpf_cnpj'])->chunkById(500, function ($vendas) use (&$resultado, $dryRun) {
            foreach ($vendas as $venda) {
                $documento = self::normalizarDocumento($venda->cpf_cnpj);
                if ($documento) {
                    $resultado['normalizadas']++;
                    if (! $dryRun) DB::table('vendas')->where('id', $venda->id)->update(['cpf_cnpj_normalizado' => $documento]);
                }
            }
        });

        if ($dryRun) {
            $ultimas = [];
            Vendas::query()->where('tabulacao_id', Tabulations::IMPLANTADO)
                ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
                ->select(['id', 'empresa_id', 'cpf_cnpj', 'data_implantacao', 'created_at'])->cursor()
                ->each(function ($venda) use (&$ultimas) {
                    $documento = self::normalizarDocumento($venda->cpf_cnpj);
                    if (! $documento) return;
                    $chave = $venda->empresa_id.':'.$documento;
                    $data = Carbon::parse($venda->data_implantacao ?: $venda->getRawOriginal('created_at'))->startOfDay();
                    if (! isset($ultimas[$chave]) || $data->gt($ultimas[$chave]['data']) || ($data->eq($ultimas[$chave]['data']) && $venda->id > $ultimas[$chave]['id'])) {
                        $ultimas[$chave] = ['data' => $data, 'id' => $venda->id];
                    }
                });
            $resultado['elegiveis'] = collect($ultimas)->filter(fn ($v) => $v['data']->copy()->addMonthsNoOverflow(24)->isPast())->count();
            return $resultado;
        }

        $query = Vendas::query()
            ->where('tabulacao_id', Tabulations::IMPLANTADO)
            ->whereNotNull('cpf_cnpj_normalizado')
            ->when($empresaId, fn (Builder $q) => $q->where('empresa_id', $empresaId))
            ->orderBy('empresa_id')->orderBy('cpf_cnpj_normalizado')
            ->orderByRaw('COALESCE(data_implantacao, DATE(created_at)) DESC')->orderByDesc('id');

        $vistos = [];
        foreach ($query->cursor() as $venda) {
            $chave = $venda->empresa_id.':'.$venda->cpf_cnpj_normalizado;
            if (isset($vistos[$chave])) continue;
            $vistos[$chave] = true;
            $dataBase = Carbon::parse($venda->data_implantacao ?: $venda->getRawOriginal('created_at'))->startOfDay();
            $elegivelDesde = $dataBase->copy()->addMonthsNoOverflow(24);
            if ($elegivelDesde->isFuture()) {
                if (! $dryRun) {
                    RenovacaoOportunidade::where('empresa_id', $venda->empresa_id)
                        ->where('documento', $venda->cpf_cnpj_normalizado)
                        ->whereNotIn('status', [RenovacaoStatus::CONVERTIDO, RenovacaoStatus::NAO_CONTATAR])
                        ->update(['status' => RenovacaoStatus::SUSPENSO, 'venda_referencia_id' => $venda->id,
                            'vendedor_original_id' => $venda->user_id ?: null, 'data_base' => $dataBase->toDateString(),
                            'elegivel_desde' => $elegivelDesde->toDateString(), 'updated_at' => now()]);
                }
                continue;
            }
            $resultado['elegiveis']++;
            $oportunidade = RenovacaoOportunidade::firstOrNew([
                'empresa_id' => $venda->empresa_id, 'documento' => $venda->cpf_cnpj_normalizado,
            ]);
            $nova = ! $oportunidade->exists;
            if ($oportunidade->status === RenovacaoStatus::NAO_CONTATAR && ! $nova) continue;
            if ($oportunidade->nova_venda_id && ! $nova) continue;
            $mudouReferencia = (int) $oportunidade->venda_referencia_id !== (int) $venda->id;
            $oportunidade->fill([
                'venda_referencia_id' => $venda->id, 'vendedor_original_id' => $venda->user_id ?: null,
                'contato_id' => $venda->contato_id ?: null, 'data_base' => $dataBase->toDateString(),
                'elegivel_desde' => $elegivelDesde->toDateString(),
            ]);
            if ($nova || $mudouReferencia || $oportunidade->status === RenovacaoStatus::SUSPENSO) {
                $oportunidade->status = RenovacaoStatus::ELEGIVEL;
                $oportunidade->recontato_em = null;
            }
            $oportunidade->save();
            $resultado[$nova ? 'criadas' : 'atualizadas']++;
        }

        if (! $dryRun) {
            RenovacaoOportunidade::query()->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
                ->whereNotIn('status', [RenovacaoStatus::CONVERTIDO, RenovacaoStatus::NAO_CONTATAR])
                ->whereDoesntHave('vendaReferencia', fn ($q) => $q->where('tabulacao_id', Tabulations::IMPLANTADO))
                ->chunkById(200, function ($items) use (&$resultado) {
                    foreach ($items as $item) { $item->update(['status' => RenovacaoStatus::SUSPENSO]); $resultado['suspensas']++; }
                });
        }
        return $resultado;
    }

    public function consulta(int $empresaId, array $filtros): LengthAwarePaginator
    {
        return RenovacaoOportunidade::query()->where('empresa_id', $empresaId)
            ->with(['vendaReferencia:id,nome_contrato,cpf_cnpj,telefone1,telefone2,email,operadora,nome_plano,valor_contrato,vidas,data_implantacao,created_at,user_id', 'vendedorOriginal:id,name', 'responsavel:id,name'])
            ->when($filtros['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filtros['responsavel_id'] ?? null, fn ($q, $v) => $q->where('responsavel_id', $v))
            ->when($filtros['vendedor_id'] ?? null, fn ($q, $v) => $q->where('vendedor_original_id', $v))
            ->when($filtros['operadora'] ?? null, fn ($q, $v) => $q->whereHas('vendaReferencia', fn ($s) => $s->where('operadora', $v)))
            ->when($filtros['busca'] ?? null, function ($q, $v) {
                $doc = preg_replace('/\D/', '', $v);
                $q->where(function ($s) use ($doc, $v) {
                    if ($doc !== '') $s->where('documento', 'like', "%{$doc}%")->orWhereHas('vendaReferencia', fn ($venda) => $venda->where('nome_contrato', 'like', "%{$v}%"));
                    else $s->whereHas('vendaReferencia', fn ($venda) => $venda->where('nome_contrato', 'like', "%{$v}%"));
                });
            })
            ->when(($filtros['status'] ?? null) !== RenovacaoStatus::REAGENDADO,
                fn ($q) => $q->where(fn ($s) => $s->where('status', '!=', RenovacaoStatus::REAGENDADO)->orWhereDate('recontato_em', '<=', today())))
            ->orderByRaw("CASE status WHEN 'ELEGIVEL' THEN 0 WHEN 'REAGENDADO' THEN 1 ELSE 2 END")
            ->orderBy('elegivel_desde')->paginate(max(10, min((int) ($filtros['per_page'] ?? 20), 100)));
    }

    public function metricas(int $empresaId): array
    {
        $q = RenovacaoOportunidade::where('empresa_id', $empresaId);
        $total = (clone $q)->count(); $contatados = (clone $q)->whereNotNull('contatada_em')->count();
        $respostas = (clone $q)->whereNotNull('respondida_em')->count();
        $cotacoes = (clone $q)->whereNotNull('cotacao_solicitada_em')->count();
        $convertidos = (clone $q)->whereNotNull('convertida_em')->count();
        return compact('total', 'contatados', 'respostas', 'cotacoes', 'convertidos') + [
            'taxa_resposta' => $contatados ? round($respostas / $contatados * 100, 1) : 0,
            'taxa_interesse' => $respostas ? round($cotacoes / $respostas * 100, 1) : 0,
            'taxa_conversao' => $cotacoes ? round($convertidos / $cotacoes * 100, 1) : 0,
            'reagendamentos_vencidos' => (clone $q)->where('status', RenovacaoStatus::REAGENDADO)->whereDate('recontato_em', '<=', today())->count(),
        ];
    }

    public function detalhe(RenovacaoOportunidade $oportunidade): RenovacaoOportunidade
    {
        $oportunidade->load(['vendaReferencia.user:id,name', 'vendedorOriginal:id,name', 'responsavel:id,name', 'interacoes.usuario:id,name']);
        $oportunidade->setAttribute('vendas_documento', Vendas::where('empresa_id', $oportunidade->empresa_id)
            ->where('cpf_cnpj_normalizado', $oportunidade->documento)->latest('id')
            ->get(['id', 'nome_contrato', 'operadora', 'nome_plano', 'tabulacao_id', 'data_implantacao', 'created_at']));
        return $oportunidade;
    }

    public function tratar(RenovacaoOportunidade $oportunidade, User $ator, array $dados): RenovacaoOportunidade
    {
        return DB::transaction(function () use ($oportunidade, $ator, $dados) {
            $oportunidade = RenovacaoOportunidade::lockForUpdate()->findOrFail($oportunidade->id);
            $status = $dados['status']; $agora = now();
            $updates = ['status' => $status, 'responsavel_id' => $oportunidade->responsavel_id ?: $ator->id];
            if (in_array($status, [RenovacaoStatus::AGUARDANDO_RESPOSTA, RenovacaoStatus::SEM_RESPOSTA], true)) $updates['contatada_em'] = $oportunidade->contatada_em ?: $agora;
            if ($status === RenovacaoStatus::EM_CONVERSA) { $updates['contatada_em'] = $oportunidade->contatada_em ?: $agora; $updates['respondida_em'] = $oportunidade->respondida_em ?: $agora; }
            if ($status === RenovacaoStatus::COTACAO_SOLICITADA) { $updates['contatada_em'] = $oportunidade->contatada_em ?: $agora; $updates['respondida_em'] = $oportunidade->respondida_em ?: $agora; $updates['cotacao_solicitada_em'] = $agora; }
            if ($status === RenovacaoStatus::REAGENDADO) $updates['recontato_em'] = $dados['recontato_em'];
            if (in_array($status, [RenovacaoStatus::SEM_INTERESSE, RenovacaoStatus::NAO_CONTATAR], true)) $updates['encerrada_em'] = $agora;
            $oportunidade->update($updates);
            RenovacaoInteracao::create(['oportunidade_id' => $oportunidade->id, 'user_id' => $ator->id, 'tipo' => $status, 'observacao' => $dados['observacao'] ?? null, 'metadados' => isset($dados['recontato_em']) ? ['recontato_em' => $dados['recontato_em']] : null]);
            if ($status === RenovacaoStatus::COTACAO_SOLICITADA) $this->encaminharCotacao($oportunidade, $ator);
            return $oportunidade->fresh();
        });
    }

    private function encaminharCotacao(RenovacaoOportunidade $oportunidade, User $ator): void
    {
        $venda = $oportunidade->vendaReferencia;
        $contato = $venda->contato_id ? Contatos::find($venda->contato_id) : Contatos::where('empresa_id', $oportunidade->empresa_id)->where('cpf', $oportunidade->documento)->first();
        if (! $contato) $contato = Contatos::create(['empresa_id' => $oportunidade->empresa_id, 'user_import_id' => $ator->id, 'nome_base' => 'RENOVACAO', 'nome_cliente' => $venda->nome_contrato, 'cpf' => $oportunidade->documento, 'telefone1' => preg_replace('/\D/', '', $venda->telefone1), 'telefone2' => preg_replace('/\D/', '', $venda->telefone2), 'email' => $venda->email]);
        $atribuicao = ContatosCorretores::where('empresa_id', $oportunidade->empresa_id)->where('contato_id', $contato->id)->whereNotNull('user_id')->first();
        $destinatario = $atribuicao?->user_id ? User::where('id', $atribuicao->user_id)->where('ativo', 'Y')->first() : User::where('id', $oportunidade->vendedor_original_id)->where('ativo', 'Y')->first();
        if (! $atribuicao && $destinatario) $atribuicao = ContatosCorretores::create(['empresa_id' => $oportunidade->empresa_id, 'contato_id' => $contato->id, 'user_id' => $destinatario->id, 'tabulacao_id' => Tabulations::PROSPECCAO, 'temperatura' => 'QUENTE']);
        $oportunidade->update(['contato_id' => $contato->id, 'lead_vendedor_id' => $destinatario?->id, 'cotacao_solicitada_em' => now()]);
        $alvos = $destinatario ? collect([$destinatario]) : User::where('empresa_id', $oportunidade->empresa_id)->where('user_role_id', UserRole::SUPERVISOR)->where('ativo', 'Y')->get();
        $alvos->each(fn ($u) => $u->notify(new CotacaoRenovacaoSolicitada($oportunidade->id, $venda->nome_contrato ?: 'Cliente', $destinatario ? route('comercial.kanban') : route('backoffice.renovacoes.index'))));
    }

    public function registrarNovaVenda(Vendas $venda): void
    {
        if (! Schema::hasTable('renovacao_oportunidades') || ! $venda->cpf_cnpj_normalizado) return;
        RenovacaoOportunidade::where('empresa_id', $venda->empresa_id)->where('documento', $venda->cpf_cnpj_normalizado)
            ->where('venda_referencia_id', '!=', $venda->id)->whereNull('convertida_em')->get()->each(function ($o) use ($venda) {
                $o->update(['status' => RenovacaoStatus::CONVERTIDO, 'nova_venda_id' => $venda->id, 'convertida_em' => now(), 'encerrada_em' => now()]);
                RenovacaoInteracao::create(['oportunidade_id' => $o->id, 'tipo' => 'CONVERSAO_AUTOMATICA', 'observacao' => "Nova venda #{$venda->id} cadastrada."]);
            });
    }
}
