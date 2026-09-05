<?php

namespace App\Http\Controllers\manager;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Manager extends Controller
{
    public function changeCompany(Request $request)
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer', 'exists:empresas,id'],
        ]);

        $user = $request->user();
        abort_unless($user->isPlatformAdmin(), 403);

        $empresa = Empresa::query()->findOrFail($data['empresa_id']);
        $fromEmpresaId = (int) $request->session()->get(
            TenantContext::SESSION_KEY,
            $user->getRawOriginal('empresa_id')
        );

        DB::table('tenant_context_switches')->insert([
            'user_id' => $user->id,
            'from_empresa_id' => $fromEmpresaId ?: null,
            'to_empresa_id' => $empresa->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);

        $request->session()->put(TenantContext::SESSION_KEY, $empresa->id);

        return redirect()->route('home.dashboard')
            ->with('status', 'success')
            ->with('message', "Empresa ativa alterada para {$empresa->nome_fantasia}.");
    }
}
