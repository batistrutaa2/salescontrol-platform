<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use LogicException;

trait BelongsToTenantThrough
{
    protected static function bootBelongsToTenantThrough(): void
    {
        static::addGlobalScope('tenant-through-parent', function (Builder $builder): void {
            $context = app(TenantContext::class);

            if (! $context->isResolved()) {
                return;
            }

            $model = $builder->getModel();
            $ownership = static::ownershipFor($model->getTable());

            $builder->whereIn(
                $model->qualifyColumn($ownership['foreign_key']),
                fn (QueryBuilder $parentQuery) => static::selectTenantParentIds(
                    $parentQuery,
                    $ownership['parent'],
                    $context->id()
                )
            );
        });

        static::saving(function (self $model): void {
            $context = app(TenantContext::class);

            if (! $context->isResolved()) {
                return;
            }

            $ownership = static::ownershipFor($model->getTable());
            $parentId = $model->getAttribute($ownership['foreign_key']);

            if (! $parentId || ! static::parentBelongsToTenant($ownership['parent'], (int) $parentId, $context->id())) {
                throw new LogicException('O recurso pai não pertence à empresa ativa.');
            }
        });
    }

    /** @return array{parent: string, foreign_key: string} */
    private static function ownershipFor(string $table): array
    {
        $ownership = config("tenancy.inherited.{$table}");

        if (! is_array($ownership)) {
            throw new LogicException("A tabela {$table} não possui regra de tenant herdado.");
        }

        return $ownership;
    }

    private static function selectTenantParentIds(QueryBuilder $query, string $table, int $empresaId): void
    {
        $query->select("{$table}.id")->from($table);

        if (in_array($table, config('tenancy.direct', []), true)) {
            $query->where("{$table}.empresa_id", $empresaId);

            return;
        }

        $ownership = static::ownershipFor($table);
        $query->whereIn(
            "{$table}.{$ownership['foreign_key']}",
            fn (QueryBuilder $parentQuery) => static::selectTenantParentIds(
                $parentQuery,
                $ownership['parent'],
                $empresaId
            )
        );
    }

    private static function parentBelongsToTenant(string $table, int $id, int $empresaId): bool
    {
        $query = DB::table($table)->where("{$table}.id", $id);

        if (in_array($table, config('tenancy.direct', []), true)) {
            return $query->where("{$table}.empresa_id", $empresaId)->exists();
        }

        $ownership = static::ownershipFor($table);
        $parentId = $query->value($ownership['foreign_key']);

        return $parentId
            && static::parentBelongsToTenant($ownership['parent'], (int) $parentId, $empresaId);
    }
}
