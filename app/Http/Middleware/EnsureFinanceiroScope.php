<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe o usuário FINANCEIRO ao módulo financeiro.
 */
class EnsureFinanceiroScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && (int) $user->user_role_id === UserRole::FINANCEIRO) {
            $routeName = optional($request->route())->getName() ?? '';

            $permitidas = str_starts_with($routeName, 'financeiro.')
                || in_array($routeName, ['logout', 'login'], true)
                || str_starts_with($routeName, 'notificacoes.');

            if (! $permitidas) {
                if ($request->expectsJson()) {
                    abort(403, 'Acesso não autorizado.');
                }

                return redirect()->route('financeiro.recebiveis.index');
            }
        }

        return $next($request);
    }
}
