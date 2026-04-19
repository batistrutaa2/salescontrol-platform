<?php

namespace App\Providers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    public const MODE_SAUDE = 'saude';
    public const MODE_BENEFICIOS = 'beneficios';
    public const SESSION_KEY = 'crm_mode';

    public function register(): void
    {
    }

    public function boot(): void
    {
        $horizontalMenuData = json_decode(file_get_contents(base_path('resources/menu/horizontalMenu.json')));
        $verticalMenuSaude = json_decode(file_get_contents(base_path('resources/menu/verticalMenu.json')));
        $verticalMenuBeneficios = json_decode(file_get_contents(base_path('resources/menu/verticalMenuBeneficios.json')));

        View::composer('*', function ($view) use ($horizontalMenuData, $verticalMenuSaude, $verticalMenuBeneficios) {
            $mode = Session::get(self::SESSION_KEY, self::MODE_SAUDE);

            $vertical = $mode === self::MODE_BENEFICIOS
                ? $verticalMenuBeneficios
                : $verticalMenuSaude;

            $view->with([
                'menuData' => [$vertical, $horizontalMenuData],
                'crmMode' => $mode,
            ]);
        });
    }
}
