<?php

namespace App\Http\Controllers\pages;

use App\Http\Controllers\Controller;
use App\Services\Agradecimento5AnosService;
use Illuminate\Support\Facades\Auth;

class Agradecimento5AnosController extends Controller
{
    public function __construct(private Agradecimento5AnosService $service)
    {
    }

    /** Marca que o usuário assistiu o agradecimento até o final. */
    public function concluir()
    {
        $this->service->marcarVisto(Auth::user());

        return response()->json(['ok' => true]);
    }

    /**
     * Replay: arma a flag de sessão (consumida uma única vez pela
     * partial) e volta para a página anterior, onde o tributo reabre.
     */
    public function rever()
    {
        session(['lk5_tributo_rever' => true]);

        return redirect()->back();
    }
}
