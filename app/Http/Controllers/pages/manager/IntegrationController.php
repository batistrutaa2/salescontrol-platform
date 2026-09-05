<?php

namespace App\Http\Controllers\pages\manager;

use App\Http\Controllers\Controller;
use App\Models\TenantServiceCredential;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(): View
    {
        return view('content.pages.manager.integrations', [
            'empresa' => $this->tenantContext->empresa(),
            'voip' => $this->voipConfiguration(),
        ]);
    }

    public function saveVoip(Request $request): RedirectResponse
    {
        $configuration = $this->voipConfiguration();
        $data = $request->validate([
            'endpoint' => ['required', 'url:http,https', 'starts_with:https://', 'max:2048'],
            'token' => [$configuration ? 'nullable' : 'required', 'string', 'min:16', 'max:512'],
            'active' => ['nullable', 'boolean'],
        ], [
            'endpoint.starts_with' => 'O endpoint precisa usar HTTPS para proteger a credencial da empresa.',
            'token.required' => 'Informe o token ao configurar a telefonia pela primeira vez.',
        ]);

        $credentials = $configuration?->credentials ?? [];
        if (filled($data['token'] ?? null)) {
            $credentials['token'] = trim($data['token']);
        }

        TenantServiceCredential::query()->updateOrCreate([
            'empresa_id' => $this->tenantContext->id(),
            'service' => TenantServiceCredential::SERVICE_VOIP_MAIS,
        ], [
            'endpoint' => rtrim($data['endpoint'], '/'),
            'credentials' => $credentials,
            'active' => $request->boolean('active'),
        ]);

        return back()->with('status', 'success')->with('message', 'Telefonia configurada somente para a empresa ativa.');
    }

    public function deleteVoip(): RedirectResponse
    {
        $this->voipConfiguration()?->delete();

        return back()->with('status', 'success')->with('message', 'Configuração de telefonia removida da empresa ativa.');
    }

    private function voipConfiguration(): ?TenantServiceCredential
    {
        return TenantServiceCredential::query()
            ->where('empresa_id', $this->tenantContext->id())
            ->where('service', TenantServiceCredential::SERVICE_VOIP_MAIS)
            ->first();
    }
}
