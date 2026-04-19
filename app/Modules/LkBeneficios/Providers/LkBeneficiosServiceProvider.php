<?php

namespace App\Modules\LkBeneficios\Providers;

use App\Modules\LkBeneficios\Repositories\Contracts\BeneficiarioRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\ContratoRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Eloquent\BeneficiarioRepository;
use App\Modules\LkBeneficios\Repositories\Eloquent\ContratoRepository;
use Illuminate\Support\ServiceProvider;

class LkBeneficiosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContratoRepositoryInterface::class, ContratoRepository::class);
        $this->app->bind(BeneficiarioRepositoryInterface::class, BeneficiarioRepository::class);
    }

    public function boot(): void
    {
    }
}
