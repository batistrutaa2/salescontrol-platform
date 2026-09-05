<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacades;

class Auth extends Controller
{
    public function login(Request $request)
    {
        if (AuthFacades::check()) {
            return redirect()->intended('dashboard');
        } else {
            $credentials = $request->only('email', 'password');

            if (AuthFacades::attempt($credentials)) {
                $request->session()->regenerate();

                if (AuthFacades::user()->ativo == 'N') {
                    AuthFacades::logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors([
                        'email' => 'Esse usuario está desativado, contate seu administrador.',
                    ]);
                }

                if (AuthFacades::user()->isPlatformAdmin()
                    && ! (int) AuthFacades::user()->getRawOriginal('empresa_id')) {
                    return redirect()->route('empresa.empresa');
                }

                if (AuthFacades::user()->user_role_id == UserRole::VENDEDOR) {
                    return redirect()->route('dashboard.vendedor');
                } elseif (AuthFacades::user()->user_role_id == UserRole::ADVOGADA) {
                    return redirect()->route('backoffice.liminar.index');
                } elseif (AuthFacades::user()->user_role_id == UserRole::FINANCEIRO) {
                    return redirect()->route('financeiro.recebiveis.index');
                } else {
                    return redirect()->to('/dashboard');
                }
            }

            return back()->withErrors([
                'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
            ]);
        }
    }
}
