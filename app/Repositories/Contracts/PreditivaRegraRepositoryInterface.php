<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PreditivaRegraRepositoryInterface
{
    public function getRegrasByEmpresa(int $empresaId, bool $apenasAtivas = false): Collection;

    public function create(int $empresaId, array $data): ?int;

    public function update(int $id, int $empresaId, array $data): bool;

    public function delete(int $id, int $empresaId): bool;

    public function toggleAtivo(int $id, int $empresaId): bool;

    public function reordenar(int $empresaId, array $ordens): bool;

    public function findById(int $id, int $empresaId);
}
