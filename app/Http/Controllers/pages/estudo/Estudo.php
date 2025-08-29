<?php

namespace App\Http\Controllers\pages\estudo;

use App\Http\Controllers\Controller;
use App\Models\Operadora;
use App\Models\Plano;
use Illuminate\Http\Request;

class Estudo extends Controller
{
    public function create()
    {
        $operadoras = Operadora::where('status', 'Y')->get(['id', 'nome']);
        return view('content.pages.estudo.create', compact('operadoras'));
    }

    public function getByOperadora($operadoraId)
    {
        $planos = Plano::where('operadora_id', $operadoraId)
            ->where('status', 'Y')
            ->get(['id', 'nome']);

        return response()->json($planos);
    }
}
