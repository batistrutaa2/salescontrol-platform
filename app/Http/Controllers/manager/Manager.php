<?php

namespace App\Http\Controllers\manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\MenuServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Manager extends Controller
{
    public function changeCompany($companyId)
    {
        $user = User::find(Auth::user()->id);
        $user->empresa_id = $companyId;
        $user->save();
        return redirect()->route('home.dashboard')
            ->with('status', 'success')
            ->with('message', 'Empresa Atualizada');
    }

    public function switchModule(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:' . MenuServiceProvider::MODE_SAUDE . ',' . MenuServiceProvider::MODE_BENEFICIOS,
        ]);

        $mode = $request->input('mode');
        $request->session()->put(MenuServiceProvider::SESSION_KEY, $mode);

        $target = $mode === MenuServiceProvider::MODE_BENEFICIOS
            ? route('lk-beneficios.dashboard')
            : route('home.dashboard');

        return redirect($target)->with('status', 'success');
    }
}
