<?php

use Laravel\Fortify\Features;

return [
    /*
    |--------------------------------------------------------------------------
    | Recursos de autenticação
    |--------------------------------------------------------------------------
    |
    | Contas são criadas exclusivamente pelo onboarding administrativo da
    | empresa. O auto cadastro público do Fortify fica deliberadamente fora
    | desta lista para não criar usuários sem um tenant validado.
    |
    */
    'features' => [
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication(),
    ],
];
