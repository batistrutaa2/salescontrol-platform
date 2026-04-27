<?php

namespace App\Modules\LkBeneficios\Providers;

use App\Modules\LkBeneficios\Repositories\Contracts\BaseSaudeRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\BeneficiarioRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\ContratoRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Eloquent\BaseSaudeRepository;
use App\Modules\LkBeneficios\Repositories\Eloquent\BeneficiarioRepository;
use App\Modules\LkBeneficios\Repositories\Eloquent\ContratoRepository;
use App\Modules\LkBeneficios\Repositories\Eloquent\LeadRepository;
use Illuminate\Support\ServiceProvider;

class LkBeneficiosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContratoRepositoryInterface::class, ContratoRepository::class);
        $this->app->bind(BeneficiarioRepositoryInterface::class, BeneficiarioRepository::class);
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        $this->app->bind(BaseSaudeRepositoryInterface::class, BaseSaudeRepository::class);
    }

    public function boot(): void
    {
    }
}
