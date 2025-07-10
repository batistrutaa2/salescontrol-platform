<?php

namespace App\Http\Controllers\manager;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class Manager extends Controller
{
    public function changeCompany($companyId){
      $user = User::find(Auth::user()->id);
      $user->empresa_id = $companyId;
      $user->save();
      return redirect()->route(route: 'home.dashboard')->with('status', 'success')->with('message', "Empresa Atualizada");
    }
}
