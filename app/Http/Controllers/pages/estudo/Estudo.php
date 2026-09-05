<?php

namespace App\Http\Controllers\pages\estudo;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\EstudoItens;
use App\Models\Estudos as EstudoModel;
use App\Models\EstudoVidas;
use App\Models\Operadora;
use App\Models\Plano;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Estudo extends Controller
{
    public function index()
    {
        $empresaId = $this->tenantId();
        $estudos = $this->estudosVisiveis()->with([
            'user' => fn ($query) => $query->tenantActor($empresaId),
            'empresa',
        ])->latest()->paginate(10);

        return view('content.pages.estudo.list', compact('estudos'));
    }

    public function create()
    {
        $operadoras = Operadora::where('empresa_id', $this->tenantId())
            ->where('status', 'Y')
            ->get(['id', 'nome']);

        return view('content.pages.estudo.create', compact('operadoras'));
    }

    public function getListStudies()
    {
        $empresaId = $this->tenantId();
        $estudos = $this->estudosVisiveis()->with([
            'user' => fn ($query) => $query->tenantActor($empresaId),
            'empresa',
        ])->get();

        return response()->json(['data' => $estudos]);
    }

    public function edit($id)
    {
        $this->estudoVisivel((int) $id);
        $operadoras = DB::table('operadoras')
            ->where('empresa_id', $this->tenantId())
            ->where('status', 'Y')
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get();

        return view('content.pages.estudo.editar', ['estudoId' => $id, 'operadoras' => $operadoras]);
    }

    public function show($id)
    {
        $estudo = $this->estudoVisivel((int) $id);

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
                ->map(fn ($v) => [
                    'faixa' => $v->faixa,
                    'qtde' => (int) $v->qtde,
                    'valor_unitario' => (float) $v->valor_unitario,
                    'total' => (float) $v->total,
                ])->toArray();

            $payloadItens[] = [
                'operadora_id' => $item->operadora_id,
                'plano_id' => $item->plano_id,
                'operadora_plano' => $item->operadora_plano,
                'coparticipacao' => $item->coparticipacao,
                'categoria' => $item->categoria,
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
        $estudo = $this->estudoVisivel((int) $id);
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'estudos' => ['required', 'array', 'min:1'],

            'estudos.*.operadora_id' => ['required', 'integer'],
            'estudos.*.plano_id' => ['required', 'integer'],
            'estudos.*.coparticipacao' => ['nullable', 'string'],
            'estudos.*.categoria' => ['nullable', 'string'],
            'estudos.*.reembolso_consulta' => ['nullable', 'numeric', 'min:0'],

            'estudos.*.vidas' => ['required', 'array', 'min:1'],
            'estudos.*.vidas.*.faixa' => ['required', 'string'],
            'estudos.*.vidas.*.qtde' => ['required', 'integer', 'min:0'],
            'estudos.*.vidas.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'estudos.*.vidas.*.total' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $opcoes = $this->resolverOpcoesDoEstudo($data['estudos'], (int) $estudo->empresa_id);

        DB::beginTransaction();
        try {
            DB::table('estudos')->where('empresa_id', $estudo->empresa_id)->where('id', $estudo->id)->update([
                'titulo' => $data['titulo'], // atualiza o título do estudo
                'updated_at' => now(),
            ]);

            // limpa e recria (simples e seguro)
            $itensIds = DB::table('estudo_itens')->where('estudo_id', $estudo->id)->pluck('id');
            if ($itensIds->count()) {
                DB::table('estudo_vidas')->whereIn('estudo_item_id', $itensIds)->delete();
            }
            DB::table('estudo_itens')->where('estudo_id', $estudo->id)->delete();

            foreach ($data['estudos'] as $indice => $est) {
                $opcao = $opcoes[$indice];

                $itemId = DB::table('estudo_itens')->insertGetId([
                    'estudo_id' => $estudo->id,
                    'operadora_id' => $opcao['operadora']->id,
                    'plano_id' => $opcao['plano']->id,
                    'operadora_plano' => $opcao['titulo'],
                    'coparticipacao' => $est['coparticipacao'] ?? '',
                    'categoria' => $est['categoria'] ?? '',
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
                        'total' => $v['qtde'] * $v['valor_unitario'],
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
        Operadora::where('empresa_id', $this->tenantId())->findOrFail($operadoraId);

        $planos = Plano::where('operadora_id', $operadoraId)
            ->where('empresa_id', $this->tenantId())
            ->where('status', 'Y')
            ->get(['id', 'nome']);

        return response()->json($planos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_empresa' => ['required', 'string', 'max:255'],
            'estudos' => ['required', 'array', 'min:1'],
            'estudos.*.operadora_id' => ['required', 'integer'],
            'estudos.*.plano_id' => ['required', 'integer'],
            'estudos.*.coparticipacao' => ['nullable', 'string', 'max:50'],
            'estudos.*.categoria' => ['nullable', 'string', 'max:100'],
            'estudos.*.reembolso' => ['nullable', 'numeric', 'min:0'],
            'estudos.*.faixas' => ['required', 'array', 'min:1'],
            'estudos.*.faixas.*.faixa' => ['required', 'string', 'max:100'],
            'estudos.*.faixas.*.qtde' => ['nullable', 'integer', 'min:0'],
            'estudos.*.faixas.*.valor_unitario' => ['nullable', 'numeric', 'min:0'],
            'estudos.*.faixas.*.total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        $empresaId = $this->tenantId();
        $opcoes = $this->resolverOpcoesDoEstudo($validated['estudos'], $empresaId);

        DB::beginTransaction();

        try {
            $estudo = EstudoModel::create([
                'user_id' => Auth::user()->id,
                'empresa_id' => $empresaId,
                'titulo' => $validated['nome_empresa'],

                'link_unico' => (string) Str::uuid(),
            ]);

            // 2️⃣ Iterar pelos itens do estudo
            foreach ($validated['estudos'] as $indice => $itemData) {
                $opcao = $opcoes[$indice];
                $item = EstudoItens::create([
                    'estudo_id' => $estudo->id,
                    'operadora_id' => $opcao['operadora']->id,
                    'plano_id' => $opcao['plano']->id,
                    'operadora_plano' => $opcao['titulo'],
                    'coparticipacao' => $itemData['coparticipacao'] ?? 'NÃO',
                    'categoria' => $itemData['categoria'] ?? '',
                    'reembolso_consulta' => $itemData['reembolso'] ?? 0,
                ]);

                // 3️⃣ Iterar pelas faixas/vidas
                foreach ($itemData['faixas'] as $faixa) {
                    EstudoVidas::create([
                        'estudo_item_id' => $item->id,
                        'faixa' => $faixa['faixa'],
                        'qtde' => $faixa['qtde'] ?? 0,
                        'valor_unitario' => $faixa['valor_unitario'] ?? 0,
                        'total' => ($faixa['qtde'] ?? 0) * ($faixa['valor_unitario'] ?? 0),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estudo salvo com sucesso!',
                'estudo_id' => $estudo->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível salvar o estudo neste momento.',
            ], 500);
        }
    }

    public function showStudy($uuid)
    {
        // O UUID opaco é a credencial pública e precisa localizar a empresa
        // antes que o escopo tenant possa ser aplicado.
        $referencia = EstudoModel::withoutGlobalScope('tenant')
            ->where('link_unico', $uuid)
            ->firstOrFail();

        return app(TenantContext::class)->run((int) $referencia->empresa_id, function () use ($uuid) {
            $estudo = EstudoModel::with(['itens.vidas'])
                ->where('link_unico', $uuid)
                ->firstOrFail();

            // Mesmo que uma FK histórica esteja adulterada, só carregamos
            // operadora e plano pertencentes ao dono do estudo.
            $estudo->load([
                'itens.operadora' => fn ($query) => $query->where('empresa_id', $estudo->empresa_id),
                'itens.plano' => fn ($query) => $query->where('empresa_id', $estudo->empresa_id),
            ]);

            return view('content.pages.estudo.show', compact('estudo'));
        });
    }

    public function delete($id)
    {
        $estudo = $this->estudoVisivel((int) $id);
        $estudo->delete();

        return response()->json(['success' => true]);
    }

    private function estudosVisiveis()
    {
        return EstudoModel::query()
            ->where('empresa_id', $this->tenantId())
            ->when(
                (int) Auth::user()->user_role_id === UserRole::VENDEDOR,
                fn ($query) => $query->where('user_id', Auth::id())
            );
    }

    private function estudoVisivel(int $id): EstudoModel
    {
        return $this->estudosVisiveis()->findOrFail($id);
    }

    private function resolverOpcoesDoEstudo(array $itens, int $empresaId): array
    {
        $resultado = [];

        foreach ($itens as $indice => $item) {
            $operadora = Operadora::query()
                ->where('empresa_id', $empresaId)
                ->where('status', 'Y')
                ->find($item['operadora_id']);
            $plano = Plano::query()
                ->where('empresa_id', $empresaId)
                ->where('operadora_id', $item['operadora_id'])
                ->where('status', 'Y')
                ->find($item['plano_id']);

            if (! $operadora || ! $plano) {
                throw ValidationException::withMessages([
                    "estudos.$indice.plano_id" => 'Selecione uma operadora e um plano ativos desta empresa.',
                ]);
            }

            $resultado[$indice] = [
                'operadora' => $operadora,
                'plano' => $plano,
                'titulo' => $operadora->nome.' - '.$plano->nome,
            ];
        }

        return $resultado;
    }
}
