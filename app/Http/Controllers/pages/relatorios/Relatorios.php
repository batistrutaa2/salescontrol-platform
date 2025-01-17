<?php

namespace App\Http\Controllers\pages\relatorios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Relatorios extends Controller
{

  public function __construct()
  {

  }

  public function getLigacoes()
  {
    return view('content.pages.relatorios.ligacoes');
  }
}
