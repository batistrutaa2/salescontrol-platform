<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\UseTenantContext;

trait UsesTenantContext
{
    public function middleware(): array
    {
        return [new UseTenantContext];
    }
}
