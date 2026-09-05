<?php

namespace Tests;

use App\Support\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * O middleware limpa o tenant ao terminar a requisição, como deve ocorrer
     * em produção. Nos testes, restaura o contexto do ator apenas para que as
     * asserções Eloquent posteriores continuem explicitamente tenant-aware.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null): TestResponse
    {
        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        $context = app(TenantContext::class);
        $context->clear();
        $user = auth()->user();

        if (! $user) {
            return $response;
        }

        $empresaId = $user->isPlatformAdmin()
            ? (int) session()->get(TenantContext::SESSION_KEY, $user->getRawOriginal('empresa_id'))
            : (int) $user->getRawOriginal('empresa_id');

        if ($empresaId > 0) {
            $context->set($empresaId);
        }

        return $response;
    }
}
