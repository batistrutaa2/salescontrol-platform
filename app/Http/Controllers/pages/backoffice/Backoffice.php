<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Backoffice extends Controller
{
  public function index()
  {
    return view("content.pages.backoffice.index");
  }


}
