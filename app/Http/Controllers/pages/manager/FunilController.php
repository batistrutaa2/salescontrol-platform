<?php

namespace App\Http\Controllers\pages\manager;

use App\Http\Controllers\Controller;
use App\Models\Tabulacoes;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FunilController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(): View
    {
        $empresaId = $this->tenantContext->id();
        $etapas = Tabulacoes::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('tipo_tabulacao')
            ->orderByRaw('ordem_kanban IS NULL')
            ->orderBy('ordem_kanban')
            ->orderBy('id')
            ->get()
            ->groupBy('tipo_tabulacao');

        return view('content.pages.manager.funil', [
            'empresa' => $this->tenantContext->empresa(),
            'funis' => [
                'C' => [
                    'titulo' => 'Funil comercial',
                    'descricao' => 'Organiza a jornada do lead até o fechamento.',
                    'etapas' => $etapas->get('C', collect()),
                ],
                'A' => [
                    'titulo' => 'Pós-venda e administrativo',
                    'descricao' => 'Acompanha contratos, pendências e implantação.',
                    'etapas' => $etapas->get('A', collect()),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $this->tenantContext->id();
        $data = $request->validate($this->rules($empresaId));

        DB::transaction(function () use ($data, $empresaId) {
            $lastPosition = Tabulacoes::query()
                ->where('empresa_id', $empresaId)
                ->where('tipo_tabulacao', $data['tipo_tabulacao'])
                ->lockForUpdate()
                ->count();

            Tabulacoes::query()->create([
                'empresa_id' => $empresaId,
                'codigo' => null,
                'descricao' => mb_strtoupper(trim($data['descricao'])),
                'tipo_tabulacao' => $data['tipo_tabulacao'],
                'efetivo' => $data['efetivo'],
                'ordem_kanban' => $this->position($lastPosition + 1),
                'status' => 'Y',
                'sub_tabulacao' => 'N',
                'prazo' => $data['prazo'] ?: null,
            ]);
        });

        return back()->with('status', 'success')->with('message', 'Etapa criada somente na empresa ativa.');
    }

    public function update(Request $request, int $tabulacao): RedirectResponse
    {
        $empresaId = $this->tenantContext->id();
        $etapa = $this->tenantStage($tabulacao);
        $data = $request->validate($this->rules($empresaId, $etapa));

        $changes = [
            'descricao' => mb_strtoupper(trim($data['descricao'])),
            'prazo' => $data['prazo'] ?: null,
        ];

        if ($etapa->codigo === null) {
            $changes['efetivo'] = $data['efetivo'];
            $changes['status'] = $data['status'];
        }

        $etapa->update($changes);

        return back()->with('status', 'success')->with('message', 'Etapa atualizada na empresa ativa.');
    }

    public function move(Request $request, int $tabulacao): RedirectResponse
    {
        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        $etapa = $this->tenantStage($tabulacao);

        DB::transaction(function () use ($data, $etapa) {
            $stages = Tabulacoes::query()
                ->where('empresa_id', $this->tenantContext->id())
                ->where('tipo_tabulacao', $etapa->tipo_tabulacao)
                ->orderByRaw('ordem_kanban IS NULL')
                ->orderBy('ordem_kanban')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $index = $stages->search(fn (Tabulacoes $stage) => $stage->id === $etapa->id);
            $target = $data['direction'] === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! $stages->has($target)) {
                return;
            }

            $ordered = $stages->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

            foreach ($ordered as $position => $stage) {
                $stage->update(['ordem_kanban' => $this->position($position + 1)]);
            }
        });

        return back()->with('status', 'success')->with('message', 'Ordem do funil atualizada.');
    }

    private function tenantStage(int $id): Tabulacoes
    {
        return Tabulacoes::query()
            ->where('empresa_id', $this->tenantContext->id())
            ->findOrFail($id);
    }

    private function rules(int $empresaId, ?Tabulacoes $stage = null): array
    {
        return [
            'descricao' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tabulacoes', 'descricao')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($stage?->id),
            ],
            'tipo_tabulacao' => [$stage ? 'sometimes' : 'required', Rule::in(['C', 'A'])],
            'efetivo' => ['required', Rule::in(['Y', 'N'])],
            'status' => [$stage ? 'required' : 'sometimes', Rule::in(['Y', 'N'])],
            'prazo' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function position(int $position): string
    {
        return str_pad((string) $position, 4, '0', STR_PAD_LEFT);
    }
}
