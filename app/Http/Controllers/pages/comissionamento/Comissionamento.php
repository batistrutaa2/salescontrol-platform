<?php

namespace App\Http\Controllers\pages\comissionamento;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ComissionamentoConfiguracoes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

class Comissionamento extends Controller
{
    public function index()
    {
        $users = User::where('empresa_id', auth()->user()->empresa_id)
            ->where('ativo', "Y")
            ->where('user_role_id', UserRole::VENDEDOR)
            ->get();

        return view('content.pages.comissionamento.index', [
            'users' => $users,
        ]);
    }


    public function getCommissioning()
    {
        $configs = ComissionamentoConfiguracoes::with('user')
            ->where('empresa_id', auth()->user()->empresa_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $configs,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'percentual' => 'required|numeric|min:0',
            'periodicidade' => 'required|in:mensal,trimestral,semestral,anual',
            'dia_fechamento' => 'required|integer|min:1|max:31',
        ]);

        $empresaId = auth()->user()->empresa_id;

        $dados = [
            'percentual' => $request->percentual,
            'periodicidade' => $request->periodicidade,
            'pagar_automaticamente' => $request->has('pagar_automaticamente'),
            'dia_fechamento' => $request->dia_fechamento,
        ];

        $comissao = ComissionamentoConfiguracoes::updateOrCreate(
            [
                'empresa_id' => $empresaId,
                'user_id' => $request->user_id
            ],
            $dados
        );

        return response()->json([
            'success' => true,
            'message' => $comissao->wasRecentlyCreated
                ? 'Comissão criada com sucesso.'
                : 'Comissão atualizada com sucesso.'
        ]);
    }


    public function destroy($id)
    {
        $config = ComissionamentoConfiguracoes::where('empresa_id', auth()->user()->empresa_id)
            ->where('id', $id)
            ->firstOrFail();

        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuração de comissão excluída com sucesso.'
        ]);
    }


}
