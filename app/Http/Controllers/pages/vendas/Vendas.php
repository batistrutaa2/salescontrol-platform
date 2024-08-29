<?php

namespace App\Http\Controllers\pages\vendas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Vendas extends Controller
{
  public function index()
  {
    return view('content.pages.vendas.index');
  }
}
