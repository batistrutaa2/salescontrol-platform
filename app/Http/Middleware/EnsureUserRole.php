<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Restringe a rota aos user_role_id informados.
     * Uso: ->middleware('role:1') ou ->middleware('role:1,4')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isPlatformAdmin() && ! in_array((string) $user->user_role_id, $roles, true))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acesso não autorizado.'], 403);
            }
            abort(403, 'Acesso não autorizado.');
        }

        return $next($request);
    }
}
