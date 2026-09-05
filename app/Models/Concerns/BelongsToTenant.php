<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $context = app(TenantContext::class);

            if (! $context->isResolved()) {
                // Sem um tenant ativo, uma consulta aparentemente inocente não
                // pode se transformar em uma leitura global de todos os clientes.
                // Fluxos administrativos deliberadamente globais precisam remover
                // este scope de forma explícita e resolver o tenant antes de operar.
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->getModel()->qualifyColumn('empresa_id'), $context->id());
        });

        static::saving(function (self $model): void {
            $context = app(TenantContext::class);

            if (! $context->isResolved()) {
                if ($model->getAttribute('empresa_id') === null) {
                    throw new LogicException('Não é permitido gravar este recurso sem uma empresa ativa.');
                }

                // Importações, seeders e rotinas de bootstrap podem persistir
                // dados para uma empresa conhecida antes de abrir um contexto.
                // O empresa_id explícito é obrigatório nesses fluxos; requisições
                // normais sempre chegam aqui com o contexto resolvido.
                return;
            }

            if ($model->getAttribute('empresa_id') === null) {
                $model->setAttribute('empresa_id', $context->id());
            }

            static::assertTenantMatches($model, $context);
        });
    }

    public function scopeForTenant(Builder $query, int $empresaId): Builder
    {
        return $query->where($this->qualifyColumn('empresa_id'), $empresaId);
    }

    private static function assertTenantMatches(self $model, TenantContext $context): void
    {
        if ((int) $model->getAttribute('empresa_id') !== $context->id()) {
            throw new LogicException('O recurso não pertence à empresa ativa.');
        }
    }
}
