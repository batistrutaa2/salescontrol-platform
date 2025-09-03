<?php

namespace App\Http\Controllers\pages\estudo;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\EstudoItens;
use App\Models\EstudoVidas;
use App\Models\Operadora;
use App\Models\Plano;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Estudos as EstudoModel;
use App\Models\EstudoItem;
use App\Models\EstudoVida;

class Estudo extends Controller
{

    public function index()
    {
        $estudos = EstudoModel::with(['user', 'empresa'])->latest()->paginate(10);
        return view('content.pages.estudo.list', compact('estudos'));
    }

    public function create()
    {
        $operadoras = Operadora::where('status', 'Y')->get(['id', 'nome']);

        return view('content.pages.estudo.create', compact('operadoras'));
    }

    public function getListStudies()
    {
        if (Auth::user()->role_id != UserRole::VENDEDOR) {
            $estudos = EstudoModel::with(['user', 'empresa'])->get();
        } else {
            $estudos = EstudoModel::where('user_id', Auth::user()->id)->with(['user', 'empresa'])->get();
        }
        return response()->json(['data' => $estudos]);
    }


    public function getByOperadora($operadoraId)
    {
        $planos = Plano::where('operadora_id', $operadoraId)
            ->where('status', 'Y')
            ->get(['id', 'nome']);

        return response()->json($planos);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $estudo = EstudoModel::create([
                'user_id' => Auth::user()->id,
                'empresa_id' => Auth::user()->empresa_id,
                'titulo' => $request->nome_empresa,
                'link_unico' => (string) Str::uuid(),
            ]);

            // 2️⃣ Iterar pelos itens do estudo
            foreach ($request->estudos as $itemData) {
                $item = EstudoItens::create([
                    'estudo_id' => $estudo->id,
                    'operadora_plano' => $itemData['titulo'],
                    'coparticipacao' => $itemData['coparticipacao'] ?? null,
                    'reembolso_consulta' => $itemData['reembolso'] ?? 0,
                ]);



                // 3️⃣ Iterar pelas faixas/vidas
                foreach ($itemData['faixas'] as $faixa) {
                    EstudoVidas::create([
                        'estudo_item_id' => $item->id,
                        'faixa' => $faixa['faixa'],
                        'qtde' => $faixa['qtde'] ?? 0,
                        'valor_unitario' => $faixa['valor_unitario'] ?? 0,
                        'total' => $faixa['total'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estudo salvo com sucesso!',
                'estudo_id' => $estudo->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar o estudo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showStudy($uuid)
    {
        $estudo = EstudoModel::with(['itens.vidas'])
            ->where('link_unico', $uuid)
            ->firstOrFail();

        return view('content.pages.estudo.show', compact('estudo'));
    }

    public function delete($id)
    {
        $estudo = EstudoModel::findOrFail($id);
        $estudo->delete();

        return response()->json(['success' => true]);
    }

}
