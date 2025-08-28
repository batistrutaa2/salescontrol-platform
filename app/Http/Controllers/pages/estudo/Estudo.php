<?php

namespace App\Http\Controllers\pages\estudo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Estudo extends Controller
{
    public function create()
    {
        return view('content.pages.estudo.create');
    }
}
