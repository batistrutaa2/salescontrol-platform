<?php

namespace App\Jobs\Concerns;

use App\Support\TenantContext;

trait RunsTenantFailureCallback
{
    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    protected function runTenantFailureCallback(string $model, int $id, callable $callback): void
    {
        $context = app(TenantContext::class);
        $context->clear();
        $empresaId = $model::withoutGlobalScope('tenant')->whereKey($id)->value('empresa_id');

        if ($empresaId === null) {
            return;
        }

        try {
            $context->run((int) $empresaId, $callback);
        } finally {
            $context->clear();
        }
    }
}
