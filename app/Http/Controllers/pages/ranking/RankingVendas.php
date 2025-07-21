<?php

namespace App\Http\Controllers\pages\ranking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RankingVendas extends Controller
{
    public function index()
    {
        return view('content.pages.ranking.index');
    }

    public function config()
    {
        return view('content.pages.ranking.config');
    }
}
