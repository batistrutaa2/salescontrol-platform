<?php

namespace App\Http\Controllers\pages\comissionamento;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Comissionamento extends Controller
{
    public function index()
    {
        return view('content.pages.comissionamento.index');
    }
}
