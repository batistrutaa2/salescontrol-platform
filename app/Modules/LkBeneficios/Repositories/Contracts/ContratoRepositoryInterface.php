<?php

namespace App\Modules\LkBeneficios\Repositories\Contracts;

use App\Modules\LkBeneficios\Models\Contrato;
use Illuminate\Database\Eloquent\Builder;

interface ContratoRepositoryInterface
{
    public function queryForDataTable(int $empresaId, array $filtros = []): Builder;

    public function findByEmpresa(int $id, int $empresaId): ?Contrato;

    public function create(array $data): Contrato;

    public function update(int $id, int $empresaId, array $data): Contrato;

    public function delete(int $id, int $empresaId): bool;

    public function countByStatus(int $empresaId): array;

    public function somaMrr(int $empresaId): float;

    public function reajustesProximos(int $empresaId, int $dias = 30);
}
