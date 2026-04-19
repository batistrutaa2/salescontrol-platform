<?php

namespace App\Http\Middleware;

use App\Providers\MenuServiceProvider;
use Closure;
use Illuminate\Http\Request;

class SetBeneficiosMode
{
    public function handle(Request $request, Closure $next)
    {
        $request->session()->put(
            MenuServiceProvider::SESSION_KEY,
            MenuServiceProvider::MODE_BENEFICIOS
        );

        return $next($request);
    }
}
