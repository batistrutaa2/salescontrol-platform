<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait RequiresTenantContext
{
    protected static function bootRequiresTenantContext(): void
    {
        static::addGlobalScope('requires-tenant-context', function (Builder $builder): void {
            if (! app(TenantContext::class)->isResolved()) {
                $builder->whereRaw('1 = 0');
            }
        });

        static::saving(function (): void {
            if (! app(TenantContext::class)->isResolved()) {
                throw new LogicException('Não é permitido gravar este recurso sem uma empresa ativa.');
            }
        });
    }
}
