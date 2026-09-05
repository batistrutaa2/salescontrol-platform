<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\TenantIntegrationCredential;
use App\Models\User;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Eloquent\ContatosRepository;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class Contatos extends Controller
{
    protected ContatosRepository $contatosRepository;

    public function __construct(ContatosRepositoryInterface $contatosRepositoryInterface)
    {
        $this->contatosRepository = $contatosRepositoryInterface;
    }

    public function collectCustomer(Request $request)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['error' => true, 'message' => 'Credencial de integração ausente.'], 401);
        }

        // O token é a única informação disponível antes da resolução do tenant.
        // Esta é uma busca global deliberada; qualquer operação posterior roda
        // dentro da empresa gravada na própria credencial.
        $credential = TenantIntegrationCredential::withoutGlobalScope('tenant')
            ->where('token_hash', hash('sha256', $token))
            ->where('active', true)
            ->first();

        if (! $credential || ! $credential->allows('leads.ads.import')) {
            return response()->json(['error' => true, 'message' => 'Credencial de integração inválida.'], 401);
        }

        return app(TenantContext::class)->run((int) $credential->empresa_id, function () use ($credential, $request) {
            $importUserIsValid = User::query()
                ->tenantActor((int) $credential->empresa_id)
                ->whereKey($credential->user_id)
                ->where('ativo', 'Y')
                ->exists();

            if (! $importUserIsValid) {
                return response()->json(['error' => true, 'message' => 'Usuário de importação indisponível.'], 403);
            }

            $credential->forceFill(['last_used_at' => now()])->save();

            return $this->contatosRepository->importarLeadsAds(
                $request->except('token'),
                (int) $credential->empresa_id,
                (int) $credential->user_id,
            );
        });
    }
}
