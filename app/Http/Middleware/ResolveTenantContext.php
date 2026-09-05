<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        // O container da aplicação pode sobreviver entre requisições (Octane,
        // workers e testes). Nunca reutilize o tenant resolvido anteriormente.
        $this->tenantContext->clear();

        $user = $request->user();

        if (! $user) {
            try {
                return $next($request);
            } finally {
                $this->tenantContext->clear();
            }
        }

        $homeEmpresaIdOriginal = $user->getRawOriginal('empresa_id');
        $homeEmpresaId = (int) $homeEmpresaIdOriginal;
        $empresaId = $homeEmpresaId;

        if ($user->isPlatformAdmin()) {
            $selectedEmpresaId = (int) $request->session()->get(TenantContext::SESSION_KEY, $homeEmpresaId);

            if ($selectedEmpresaId > 0 && Empresa::query()->whereKey($selectedEmpresaId)->exists()) {
                $empresaId = $selectedEmpresaId;
            } else {
                $request->session()->forget(TenantContext::SESSION_KEY);
            }
        } else {
            $request->session()->forget(TenantContext::SESSION_KEY);
        }

        if ($empresaId < 1 || ! Empresa::query()->whereKey($empresaId)->exists()) {
            if ($user->isPlatformAdmin() && $this->allowsWithoutTenant($request)) {
                try {
                    return $next($request);
                } finally {
                    $this->tenantContext->clear();
                }
            }

            if ($user->isPlatformAdmin()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Selecione uma empresa ativa antes de continuar.',
                        'code' => 'tenant_required',
                    ], 409);
                }

                return redirect()->route('empresa.empresa')
                    ->with('status', 'warning')
                    ->with('message', 'Selecione ou cadastre uma empresa antes de acessar a operação.');
            }

            abort(403, 'Usuário sem empresa válida.');
        }

        $this->tenantContext->set($empresaId);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }

    private function allowsWithoutTenant(Request $request): bool
    {
        return $request->routeIs('empresa.*', 'manager.changeCompany', 'logout');
    }
}
