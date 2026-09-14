<?php

namespace App\Services;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\BoletoAgenda;
use App\Models\BoletoLembrete;
use App\Models\User;
use App\Models\Vendas;
use App\Notifications\BoletoVencimentoNotification;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BoletoLembreteService
{
    public function implantados(int $empresaId): Builder
    {
        return Vendas::query()->where('vendas.empresa_id', $empresaId)
            ->whereHas('tabulacao', fn ($q) => $q->where('empresa_id', $empresaId)->where('codigo', TabulationCode::IMPLANTADO));
    }

    public function pendentes(int $empresaId): Builder
    {
        return BoletoLembrete::query()->where('empresa_id', $empresaId)->whereNull('tratado_em')
            ->whereDate('vencimento', '<=', CarbonImmutable::today('America/Sao_Paulo'))
            ->where(fn ($q) => $q->whereNull('venda_id')->orWhereHas('venda', fn ($v) => $v->whereIn('vendas.id', $this->implantados($empresaId)->select('vendas.id'))));
    }

    public function configurar(Vendas $venda, User $ator, array $dados): BoletoAgenda
    {
        return DB::transaction(function () use ($venda, $ator, $dados) {
            $venda = $this->implantados((int) $venda->empresa_id)->whereKey($venda->id)->lockForUpdate()->firstOrFail();
            $agenda = BoletoAgenda::where('venda_id', $venda->id)->lockForUpdate()->first();
            $proximo = $this->validarProximoVencimento($agenda, $dados);

            return BoletoAgenda::updateOrCreate(['venda_id' => $venda->id], [
                'empresa_id' => $venda->empresa_id,
                'dia_vencimento' => $dados['dia_vencimento'],
                'proximo_vencimento' => $proximo->toDateString(),
                'ativo' => $dados['ativo'],
                'configurado_por' => $ator->id,
            ]);
        });
    }

    private function validarProximoVencimento(?BoletoAgenda $agenda, array $dados): CarbonImmutable
    {
        $proximo = CarbonImmutable::parse($dados['proximo_vencimento'], 'America/Sao_Paulo');
        if ($proximo->day !== min((int) $dados['dia_vencimento'], $proximo->daysInMonth)) {
            throw ValidationException::withMessages(['proximo_vencimento' => 'A data deve corresponder ao dia mensal escolhido (ou ao último dia do mês).']);
        }
        if ($agenda && BoletoLembrete::where('agenda_id', $agenda->id)->where('competencia', $proximo->startOfMonth()->toDateString())->exists()) {
            throw ValidationException::withMessages(['proximo_vencimento' => 'Este mês já possui um lembrete. Escolha o próximo mês para preservar o histórico.']);
        }

        return $proximo;
    }

    public function configurarManual(?BoletoAgenda $agenda, User $ator, int $empresaId, array $dados): BoletoAgenda
    {
        return DB::transaction(function () use ($agenda, $ator, $empresaId, $dados) {
            if ($agenda) {
                $agenda = BoletoAgenda::where('empresa_id', $empresaId)->whereNull('venda_id')->whereKey($agenda->id)->lockForUpdate()->firstOrFail();
            }
            $proximo = $this->validarProximoVencimento($agenda, $dados);
            $agenda ??= new BoletoAgenda;
            $agenda->fill([
                'empresa_id' => $empresaId,
                'nome_cliente' => $dados['nome_cliente'],
                'referencia' => $dados['referencia'] ?? null,
                'dia_vencimento' => $dados['dia_vencimento'],
                'proximo_vencimento' => $proximo->toDateString(),
                'ativo' => $dados['ativo'],
                'configurado_por' => $ator->id,
            ])->save();

            return $agenda;
        });
    }

    public function sincronizar(?int $empresaId = null): int
    {
        $hoje = CarbonImmutable::today('America/Sao_Paulo');
        $total = 0;
        BoletoAgenda::withoutGlobalScope('tenant')->where('ativo', true)
            ->whereDate('proximo_vencimento', '<=', $hoje)
            ->when($empresaId !== null, fn ($q) => $q->where('empresa_id', $empresaId))
            ->select(['id', 'empresa_id', 'venda_id'])->chunkById(100, function ($agendas) use ($hoje, &$total) {
                foreach ($agendas as $item) {
                    $total += app(TenantContext::class)->run((int) $item->empresa_id, fn () => DB::transaction(function () use ($item, $hoje) {
                        // Same lock order as configuration: contract, then schedule.
                        $venda = $item->venda_id ? $this->implantados((int) $item->empresa_id)->whereKey($item->venda_id)->lockForUpdate()->first() : null;
                        if ($item->venda_id && ! $venda) {
                            return 0;
                        }
                        $agenda = BoletoAgenda::whereKey($item->id)->lockForUpdate()->first();
                        if (! $agenda || ! $agenda->ativo) {
                            return 0;
                        }
                        $criados = 0;
                        while ($agenda->proximo_vencimento->lte($hoje)) {
                            $lembrete = BoletoLembrete::firstOrCreate([
                                'agenda_id' => $agenda->id,
                                'competencia' => $agenda->proximo_vencimento->startOfMonth()->toDateString(),
                            ], [
                                'empresa_id' => $agenda->empresa_id,
                                'venda_id' => $agenda->venda_id,
                                'nome_cliente' => $agenda->nome_cliente,
                                'referencia' => $agenda->referencia,
                                'vencimento' => $agenda->proximo_vencimento->toDateString(),
                            ]);
                            if ($lembrete->wasRecentlyCreated) {
                                $lembrete->setRelation('venda', $venda);
                                User::query()->tenantMember((int) $agenda->empresa_id)->where('ativo', 'Y')
                                    ->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE])
                                    ->each(fn (User $user) => $user->notify(new BoletoVencimentoNotification($lembrete)));
                                $criados++;
                            }
                            $mesSeguinte = $agenda->proximo_vencimento->startOfMonth()->addMonth();
                            $agenda->proximo_vencimento = $mesSeguinte->day(min((int) $agenda->dia_vencimento, $mesSeguinte->daysInMonth));
                        }
                        $agenda->save();

                        return $criados;
                    }));
                }
            });

        return $total;
    }

    public function tratar(BoletoLembrete $lembrete, User $ator, ?string $observacao): void
    {
        DB::transaction(function () use ($lembrete, $ator, $observacao) {
            $lembrete = $this->pendentes((int) $lembrete->empresa_id)->whereKey($lembrete->id)->lockForUpdate()->first();
            if (! $lembrete) {
                return;
            }
            $lembrete->update(['tratado_em' => now(), 'tratado_por' => $ator->id, 'observacao' => $observacao]);
            DB::table('notifications')->where('type', BoletoVencimentoNotification::class)
                ->where('data->empresa_id', (int) $lembrete->empresa_id)
                ->where('data->lembrete_id', $lembrete->id)->whereNull('read_at')
                ->update(['read_at' => now(), 'updated_at' => now()]);
        });
    }
}
