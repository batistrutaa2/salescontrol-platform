<?php

namespace App\Jobs\Middleware;

use App\Models\ComercialReunioes;
use App\Models\VendaDocumento;
use App\Models\Vendas;
use App\Support\TenantContext;
use Closure;
use Illuminate\Support\Facades\DB;

class UseTenantContext
{
    public function handle(object $job, Closure $next): void
    {
        $context = app(TenantContext::class);
        $context->clear();
        $empresaId = $this->resolveEmpresaId($job);

        if ($empresaId === null) {
            return;
        }

        try {
            $context->set($empresaId);
            $next($job);
        } finally {
            $context->clear();
        }
    }

    private function resolveEmpresaId(object $job): ?int
    {
        if (isset($job->empresaId)) {
            $empresaId = (int) $job->empresaId;

            return DB::table('empresas')->where('id', $empresaId)->exists() ? $empresaId : null;
        }

        if (isset($job->vendaId)) {
            return $this->modelEmpresaId(Vendas::class, (int) $job->vendaId);
        }

        if (isset($job->documentoId)) {
            return $this->modelEmpresaId(VendaDocumento::class, (int) $job->documentoId);
        }

        if (isset($job->reuniaoId)) {
            return $this->modelEmpresaId(ComercialReunioes::class, (int) $job->reuniaoId);
        }

        return null;
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private function modelEmpresaId(string $model, int $id): ?int
    {
        $empresaId = $model::withoutGlobalScope('tenant')->whereKey($id)->value('empresa_id');

        return $empresaId === null ? null : (int) $empresaId;
    }
}
