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


    public function edit($id)
    {
        $operadoras = DB::table('operadoras')->select('id', 'nome')->orderBy('nome')->get();
        return view('content.pages.estudo.editar', ['estudoId' => $id, 'operadoras' => $operadoras]);
    }

    public function show($id)
    {
        $estudo = DB::table('estudos')->where('id', $id)->first();
        if (!$estudo) {
            return response()->json(['message' => 'Estudo não encontrado'], 404);
        }

        $itens = DB::table('estudo_itens')
            ->where('estudo_id', $id)
            ->orderBy('id')
            ->get();

        $payloadItens = [];
        foreach ($itens as $item) {
            $vidas = DB::table('estudo_vidas')
                ->where('estudo_item_id', $item->id)
                ->orderBy('id')
                ->get()
                ->map(fn($v) => [
                    'faixa' => $v->faixa,
                    'qtde' => (int) $v->qtde,
                    'valor_unitario' => (float) $v->valor_unitario,
                    'total' => (float) $v->total,
                ])->toArray();

            $payloadItens[] = [
                'operadora_plano' => $item->operadora_plano,
                'coparticipacao' => $item->coparticipacao,
                'reembolso_consulta' => (float) $item->reembolso_consulta,
                'vidas' => $vidas,
            ];
        }

        return response()->json([
            'id' => $estudo->id,
            'titulo' => $estudo->titulo, // campo da tabela estudos
            'estudos' => $payloadItens,   // mantive a chave "estudos" para não quebrar o front
        ]);
    }


    public function update($id, Request $request)
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'estudos' => ['required', 'array', 'min:1'],

            'estudos.*.operadora_plano' => ['required', 'string'],
            'estudos.*.coparticipacao' => ['nullable', 'string'],
            'estudos.*.reembolso_consulta' => ['nullable', 'numeric', 'min:0'],

            'estudos.*.vidas' => ['required', 'array', 'min:1'],
            'estudos.*.vidas.*.faixa' => ['required', 'string'],
            'estudos.*.vidas.*.qtde' => ['required', 'integer', 'min:0'],
            'estudos.*.vidas.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'estudos.*.vidas.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            DB::table('estudos')->where('id', $id)->update([
                'titulo' => $data['titulo'], // atualiza o título do estudo
                'updated_at' => now(),
            ]);

            // limpa e recria (simples e seguro)
            $itensIds = DB::table('estudo_itens')->where('estudo_id', $id)->pluck('id');
            if ($itensIds->count()) {
                DB::table('estudo_vidas')->whereIn('estudo_item_id', $itensIds)->delete();
            }
            DB::table('estudo_itens')->where('estudo_id', $id)->delete();

            foreach ($data['estudos'] as $est) {
                $itemId = DB::table('estudo_itens')->insertGetId([
                    'estudo_id' => $id,
                    'operadora_plano' => $est['operadora_plano'],
                    'coparticipacao' => $est['coparticipacao'] ?? '',
                    'reembolso_consulta' => $est['reembolso_consulta'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($est['vidas'] as $v) {
                    DB::table('estudo_vidas')->insert([
                        'estudo_item_id' => $itemId,
                        'faixa' => $v['faixa'],
                        'qtde' => $v['qtde'],
                        'valor_unitario' => $v['valor_unitario'],
                        'total' => $v['total'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar estudo'], 500);
        }
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
