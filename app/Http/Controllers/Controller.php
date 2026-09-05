<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function tenantId(): int
    {
        return app(TenantContext::class)->id();
    }

    /**
     * Ações que criam posse operacional (lock, chamada, agenda, progresso etc.)
     * nunca podem usar a identidade global do master como se fosse colaborador.
     */
    protected function tenantMemberOrAbort(?Authenticatable $user = null): Authenticatable
    {
        $user ??= auth()->user();

        abort_if(
            ! $user
                || (method_exists($user, 'isPlatformAdmin') && $user->isPlatformAdmin())
                || (int) $user->getAttribute('empresa_id') !== $this->tenantId(),
            403,
            'Esta ação exige um usuário operacional da empresa ativa.'
        );

        return $user;
    }

    protected function validateFileUploadExcel(Request $request)
    {

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $allowedExtensions = ['xls', 'xlsx'];

        if (! in_array($extension, $allowedExtensions)) {
            throw ValidationException::withMessages([
                'file' => 'O arquivo deve ser do tipo Excel (.xls, .xlsx).',
            ]);
        }
    }

    protected function getColorText($status)
    {

        if ($status === 'FRIO') {
            return 'info';
        } elseif ($status === 'MORNO') {
            return 'warning';
        } else {
            return 'danger';
        }
    }
}
