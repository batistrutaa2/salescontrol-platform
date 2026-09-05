<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $horizontalMenuData = json_decode(file_get_contents(base_path('resources/menu/horizontalMenu.json')));
        $verticalMenu = json_decode(file_get_contents(base_path('resources/menu/verticalMenu.json')));

        View::composer('*', function ($view) use ($horizontalMenuData, $verticalMenu) {
            $view->with([
                'menuData' => [$verticalMenu, $horizontalMenuData],
            ]);
        });
    }
}
