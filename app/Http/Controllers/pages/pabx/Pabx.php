<?php

namespace App\Http\Controllers\pages\pabx;

use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Contatos;
use App\Models\TenantServiceCredential;
use App\Modules\pabx\voipmais\SdkVoipMais;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use App\Repositories\Contracts\RamaisRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class Pabx extends Controller
{
    public function __construct(
        private readonly RamaisRepositoryInterface $ramaisRepository,
        private readonly UsuariosRepositoryInterface $usuariosRepository,
        private readonly EmpresaRepositoryInterface $empresaRepository,
        private readonly LigacoesRepositoryInterface $ligacoesRepository,
        private readonly ContatosCorretoresRepositoryInterface $contatosCorretores,
    ) {}

    public function index()
    {
        $user = Auth::user();
        $empresaId = (int) $this->tenantId();

        return view('content.pages.pabx.cadastroRamais', [
            'usuarios' => $this->usuariosRepository->usersAccordingToPermission(
                (string) $user->user_role_id,
                (string) $empresaId,
                (string) $user->id
            ),
            'tipo_usuario' => $user->user_role_id,
            'companies' => $this->empresaRepository->find($empresaId),
        ]);
    }

    public function getRamais(): JsonResponse
    {
        return response()->json([
            'data' => $this->ramaisRepository->getRamais(app(TenantContext::class)->id()),
        ]);
    }

    public function createramal(Request $request): RedirectResponse
    {
        $empresaId = $this->tenantId();
        $validated = $request->validate([
            'usuario_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('empresa_id', $empresaId)
                    ->where('is_platform_admin', false)
                    ->where('ativo', 'Y')),
            ],
            'ramal' => ['required', 'string', 'max:20', 'regex:/^[0-9*#+-]+$/'],
        ]);

        $this->ramaisRepository->create(
            $empresaId,
            (int) $validated['usuario_id'],
            $validated['ramal']
        );

        return redirect()->back()
            ->with('status', 'success')
            ->with('message', 'Ramal cadastrado com sucesso');
    }

    public function clickToCall(Request $request): JsonResponse
    {
        $user = $this->tenantMemberOrAbort($request->user());
        $empresaId = $this->tenantId();
        $validated = $request->validate([
            'contato_id' => [
                'required',
                'integer',
                Rule::exists('contatos', 'id')->where('empresa_id', $empresaId),
            ],
            'telefone' => ['required', 'string', 'max:30'],
        ]);
        $contato = Contatos::query()->whereKey($validated['contato_id'])->firstOrFail();

        if (! $user->isPlatformAdmin() && (int) $user->user_role_id === UserRole::VENDEDOR) {
            $atribuido = $this->contatosCorretores->getTabulationId(
                (int) $contato->id,
                $empresaId,
                (int) $user->id
            );
        } else {
            $atribuido = $this->contatosCorretores->getTabulationId((int) $contato->id, $empresaId);
        }

        if (! $atribuido) {
            return response()->json(['error' => true, 'message' => 'Contato não disponível para este usuário.'], 403);
        }

        $telefoneNormalizado = Helpers::cleanSpecialCharactersTelefone($validated['telefone']);
        $telefonesDoContato = collect([$contato->telefone1, $contato->telefone2, $contato->telefone3])
            ->map(fn ($telefone) => Helpers::cleanSpecialCharactersTelefone($telefone))
            ->filter()
            ->unique();

        if (! $telefoneNormalizado || ! $telefonesDoContato->contains($telefoneNormalizado)) {
            return response()->json(['error' => true, 'message' => 'Telefone não pertence ao contato selecionado.'], 422);
        }

        $ramal = $this->ramaisRepository->getRamal($empresaId, (int) $user->id);

        if (! $ramal) {
            return response()->json(['error' => true, 'message' => 'Parâmetros de ramal não foram cadastrados.'], 404);
        }

        $destino = Helpers::cleanSpecialCharacters($validated['telefone']);
        $integration = TenantServiceCredential::query()
            ->where('empresa_id', $empresaId)
            ->where('service', TenantServiceCredential::SERVICE_VOIP_MAIS)
            ->where('active', true)
            ->first();
        $endpoint = trim((string) $integration?->endpoint);
        $token = trim((string) data_get($integration?->credentials, 'token'));

        if (! filter_var($endpoint, FILTER_VALIDATE_URL) || $token === '') {
            return response()->json([
                'error' => true,
                'message' => 'Integração de telefonia não configurada para esta empresa.',
            ], 503);
        }

        $response = (new SdkVoipMais($endpoint, $token))->makeClickToCall($ramal->ramal, $destino);

        if (($response['success'] ?? true) === false || ! isset($response['data'])) {
            return response()->json([
                'error' => true,
                'message' => $response['message'] ?? 'A operadora não iniciou a chamada.',
            ], 502);
        }

        $this->ligacoesRepository->create([
            'empresa_id' => $empresaId,
            'user_id' => $user->id,
            'contato_id' => $contato->id,
            'telefone' => $destino,
            'status' => $atribuido->tabulacao_id,
            'id_call' => $response['idCall'] ?? '',
        ]);

        return response()->json([
            'error' => false,
            'message' => $response['data'],
        ], 201);
    }
}
