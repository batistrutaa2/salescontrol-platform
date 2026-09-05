<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\UseWhatsappTenantContext;

trait UsesWhatsappTenantContext
{
    public function middleware(): array
    {
        return [new UseWhatsappTenantContext];
    }
}
