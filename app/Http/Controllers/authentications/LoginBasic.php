<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth as AuthFacades;
use Illuminate\Http\Request;

class LoginBasic extends Controller
{
  public function index()
  {
    if (AuthFacades::check()) {
      return redirect()->intended('dashboard');
    }

    $pageConfigs = ['myLayout' => 'blank'];
    return view('content.authentications.auth-login', ['pageConfigs' => $pageConfigs]);
  }

  public function logout(Request $request)
  {
    AuthFacades::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
  }
}
