<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait ValidatesTenantUserReferences
{
    protected static function shouldValidateTenantReference(Model $model, string $attribute): bool
    {
        return ! $model->exists || $model->isDirty($attribute) || $model->isDirty('empresa_id');
    }

    protected static function assertTenantMember(int $empresaId, mixed $userId, string $relationship, bool $nullable = false): void
    {
        if ($userId === null && $nullable) {
            return;
        }

        $valid = $userId !== null && User::query()
            ->tenantMember($empresaId)
            ->whereKey((int) $userId)
            ->exists();

        if (! $valid) {
            throw new LogicException("O usuário de {$relationship} não é membro operacional da empresa.");
        }
    }

    protected static function assertTenantActor(int $empresaId, mixed $userId, string $relationship, bool $nullable = false): void
    {
        if ($userId === null && $nullable) {
            return;
        }

        $valid = $userId !== null && User::query()
            ->tenantActor($empresaId)
            ->whereKey((int) $userId)
            ->exists();

        if (! $valid) {
            throw new LogicException("O usuário de {$relationship} não pode atuar nesta empresa.");
        }
    }
}
