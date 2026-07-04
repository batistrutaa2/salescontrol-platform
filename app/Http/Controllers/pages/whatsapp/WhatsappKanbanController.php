<?php

namespace App\Http\Controllers\pages\whatsapp;

use App\Http\Controllers\Controller;
use App\UseCases\WhatsappConexaoUseCase;
use App\UseCases\WhatsappConversaUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WhatsappKanbanController extends Controller
{
    public function __construct(
        private WhatsappConversaUseCase $conversaUseCase,
        private WhatsappConexaoUseCase $conexaoUseCase
    ) {}

    public function index()
    {
        $podeConectar = (int) Auth::user()->user_role_id === \App\Enums\UserRole::VENDEDOR;

        return view('content.pages.whatsapp.kanban', [
            'typeUserLogeed' => Auth::user()->role->tipo_usuario,
            'podeConectar' => $podeConectar,
            'statusConexao' => $podeConectar ? $this->conexaoUseCase->status(Auth::user()) : null,
        ]);
    }

    public function getBoardData(): JsonResponse
    {
        return response()->json($this->conversaUseCase->getBoardData(Auth::user()));
    }

    public function changeStatusConversa(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'conversa_id' => 'required|integer',
            'tabulacao_id' => 'required|integer|exists:tabulacoes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $alterado = $this->conversaUseCase->changeStatus(
            Auth::user(),
            (int) $request->input('conversa_id'),
            (int) $request->input('tabulacao_id')
        );

        if (! $alterado) {
            return response()->json(['success' => false, 'message' => 'Conversa não encontrada.'], 404);
        }

        return response()->json(['success' => true]);
    }
}
