<?php

use App\Http\Middleware\EnsureAdvogadaScope;
use App\Http\Middleware\EnsureFinanceiroScope;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\ResolveTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // O tenant precisa estar resolvido antes do route model binding; do
        // contrário, models fail-closed seriam procurados sem empresa ativa.
        $middleware->web(remove: [SubstituteBindings::class], append: [
            LocaleMiddleware::class,
            ResolveTenantContext::class,
            SubstituteBindings::class,
            EnsureAdvogadaScope::class,
            EnsureFinanceiroScope::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'platform.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
        ]);

        // Webhooks da Evolution API (WhatsApp) chegam sem sessão/CSRF
        $middleware->validateCsrfTokens(except: [
            'webhook/whatsapp/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
