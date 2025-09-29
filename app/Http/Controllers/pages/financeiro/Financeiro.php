<?php

namespace App\Http\Controllers\pages\financeiro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Financeiro extends Controller
{
    public function regrasRecebimentos()
    {
        return view('content.pages.financeiro.regras-recebimentos');
    }
}
