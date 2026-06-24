<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tranca o usuário ADVOGADA no kanban de liminar.
 *
 * A advogada é um acesso externo e restrito: só pode usar as rotas
 * `backoffice.liminar.*` (kanban, detalhe, mover, download de documentos).
 * Qualquer outra rota é bloqueada — JSON recebe 403, navegação é redirecionada
 * de volta ao kanban.
 */
class EnsureAdvogadaScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && (int) $user->user_role_id === UserRole::ADVOGADA) {
            $routeName = optional($request->route())->getName() ?? '';

            $permitidas = str_starts_with($routeName, 'backoffice.liminar.')
                || in_array($routeName, ['logout', 'login'], true)
                || str_starts_with($routeName, 'notificacoes.');

            if (! $permitidas) {
                if ($request->expectsJson()) {
                    abort(403, 'Acesso não autorizado.');
                }

                return redirect()->route('backoffice.liminar.index');
            }
        }

        return $next($request);
    }
}
