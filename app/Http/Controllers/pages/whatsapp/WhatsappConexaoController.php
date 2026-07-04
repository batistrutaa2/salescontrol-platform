<?php

namespace App\Http\Controllers\pages\whatsapp;

use App\Http\Controllers\Controller;
use App\UseCases\WhatsappConexaoUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WhatsappConexaoController extends Controller
{
    public function __construct(private WhatsappConexaoUseCase $conexaoUseCase) {}

    public function conectar(): JsonResponse
    {
        try {
            $resultado = $this->conexaoUseCase->conectar(Auth::user());

            return response()->json(['success' => true, 'data' => $resultado]);
        } catch (\Throwable $e) {
            Log::error('Whatsapp: erro ao conectar instância', [
                'user_id' => Auth::id(),
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível iniciar a conexão. Tente novamente em instantes.',
            ], 500);
        }
    }

    public function status(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->conexaoUseCase->status(Auth::user())]);
    }

    public function qr(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['qrcode' => $this->conexaoUseCase->qrAtual(Auth::user())]]);
    }

    public function desconectar(): JsonResponse
    {
        $this->conexaoUseCase->desconectar(Auth::user());

        return response()->json(['success' => true]);
    }
}
