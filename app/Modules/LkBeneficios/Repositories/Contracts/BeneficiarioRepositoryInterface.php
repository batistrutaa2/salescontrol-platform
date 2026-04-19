<?php

namespace App\Modules\LkBeneficios\Repositories\Contracts;

use App\Modules\LkBeneficios\Models\Beneficiario;
use Illuminate\Database\Eloquent\Collection;

interface BeneficiarioRepositoryInterface
{
    public function listarPorContrato(int $contratoId): Collection;

    public function incluir(int $contratoId, array $data, int $userId): Beneficiario;

    public function excluir(int $id, int $contratoId, int $userId, ?string $motivo = null): bool;
}
