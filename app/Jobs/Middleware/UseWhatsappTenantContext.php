<?php

namespace App\Jobs\Middleware;

use App\Models\Contatos;
use App\Models\WhatsappConversa;
use App\Models\WhatsappInstancia;
use App\Models\WhatsappMensagem;
use App\Support\TenantContext;
use Closure;

class UseWhatsappTenantContext
{
    public function handle(object $job, Closure $next): void
    {
        $context = app(TenantContext::class);
        $context->clear();
        $empresaId = $this->resolveEmpresaId($job);

        if ($empresaId === null) {
            // O registro pode ter sido apagado entre o despacho e a execução.
            // Sem uma empresa verificável, o handler não pode executar.
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
        if (isset($job->instanciaId)) {
            return $this->empresaId(WhatsappInstancia::class, (int) $job->instanciaId);
        }

        if (isset($job->conversaId)) {
            return $this->empresaId(WhatsappConversa::class, (int) $job->conversaId);
        }

        if (isset($job->mensagemId)) {
            return $this->empresaId(WhatsappMensagem::class, (int) $job->mensagemId);
        }

        if (isset($job->contatoId)) {
            return $this->empresaId(Contatos::class, (int) $job->contatoId);
        }

        return null;
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private function empresaId(string $model, int $id): ?int
    {
        $empresaId = $model::withoutGlobalScope('tenant')->whereKey($id)->value('empresa_id');

        return $empresaId === null ? null : (int) $empresaId;
    }
}
