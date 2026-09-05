<?php

namespace App\Http\Controllers\pages\backoffice;

use App\Enums\RenovacaoStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\RenovacaoInteracao;
use App\Models\RenovacaoOportunidade;
use App\Models\User;
use App\Models\Vendas;
use App\Services\RenovacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RenovacaoController extends Controller
{
    public function __construct(private RenovacaoService $service) {}

    public function index(Request $request)
    {
        $empresaId = $this->tenantId();

        return view('content.pages.backoffice.renovacoes', [
            'usuarios' => User::query()->tenantMember($empresaId)->where('ativo', 'Y')->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE])->orderBy('name')->get(['id', 'name']),
            'vendedores' => User::query()->tenantMember($empresaId)->where('user_role_id', UserRole::VENDEDOR)->orderBy('name')->get(['id', 'name', 'ativo']),
            'operadoras' => Vendas::where('empresa_id', $empresaId)->whereNotNull('operadora')->distinct()->orderBy('operadora')->pluck('operadora'),
            'status' => RenovacaoStatus::tratativas(),
        ]);
    }

    public function dados(Request $request): JsonResponse
    {
        $empresaId = $this->tenantId();
        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', Rule::in(RenovacaoStatus::all())],
            'responsavel_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('is_platform_admin', false))],
            'vendedor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('is_platform_admin', false))],
            'operadora' => ['nullable', 'string', 'max:160'],
            'per_page' => ['nullable', 'integer', 'between:10,100'],
        ]);

        return response()->json($this->service->consulta($empresaId, $filtros));
    }

    public function metricas(Request $request): JsonResponse
    {
        return response()->json($this->service->metricas($this->tenantId()));
    }

    public function show(Request $request, RenovacaoOportunidade $oportunidade): JsonResponse
    {
        $this->garantirEmpresa($request, $oportunidade);

        return response()->json($this->service->detalhe($oportunidade));
    }

    public function tratar(Request $request, RenovacaoOportunidade $oportunidade): JsonResponse
    {
        $this->garantirEmpresa($request, $oportunidade);
        $dados = $request->validate([
            'status' => ['required', 'in:'.implode(',', RenovacaoStatus::tratativas())],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'recontato_em' => ['required_if:status,'.RenovacaoStatus::REAGENDADO, 'nullable', 'date', 'after_or_equal:today'],
        ]);

        return response()->json(['message' => 'Tratativa registrada.', 'data' => $this->service->tratar($oportunidade, $request->user(), $dados)]);
    }

    public function atribuir(Request $request, RenovacaoOportunidade $oportunidade): JsonResponse
    {
        $this->garantirEmpresa($request, $oportunidade);
        $dados = $request->validate([
            'responsavel_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('empresa_id', $this->tenantId())->where('is_platform_admin', false)),
            ],
        ]);
        $responsavelId = $dados['responsavel_id'] ?? null;
        if ($responsavelId && ! User::query()->tenantMember($this->tenantId())->whereKey($responsavelId)->whereIn('user_role_id', [UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE])->where('ativo', 'Y')->exists()) {
            abort(422, 'Responsável inválido.');
        }
        $oportunidade->update(['responsavel_id' => $responsavelId]);
        RenovacaoInteracao::create(['oportunidade_id' => $oportunidade->id, 'user_id' => $request->user()->id, 'tipo' => 'RESPONSAVEL_ALTERADO', 'metadados' => $dados]);

        return response()->json(['message' => 'Responsável atualizado.']);
    }

    public function reabrir(Request $request, RenovacaoOportunidade $oportunidade): JsonResponse
    {
        $this->garantirEmpresa($request, $oportunidade);
        abort_unless(in_array((int) $request->user()->user_role_id, [UserRole::SUPERVISOR, UserRole::DEVELOPER], true), 403);
        $oportunidade->update(['status' => RenovacaoStatus::ELEGIVEL, 'encerrada_em' => null, 'recontato_em' => null]);
        RenovacaoInteracao::create(['oportunidade_id' => $oportunidade->id, 'user_id' => $request->user()->id, 'tipo' => 'REABERTA']);

        return response()->json(['message' => 'Oportunidade reaberta.']);
    }

    private function garantirEmpresa(Request $request, RenovacaoOportunidade $oportunidade): void
    {
        abort_unless((int) $oportunidade->empresa_id === $this->tenantId(), 404);
    }
}
