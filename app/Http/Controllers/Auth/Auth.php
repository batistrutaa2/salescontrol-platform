<?php

namespace App\Http\Controllers\Auth;

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
        return redirect()->intended('dashboard');
      }

      return back()->withErrors([
        'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
      ]);
    }
  }
}
