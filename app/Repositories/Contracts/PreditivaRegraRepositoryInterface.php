<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PreditivaRegraRepositoryInterface
{
    public function getRegrasByEmpresa(int $empresaId, bool $apenasAtivas = false): Collection;

    public function create(array $data): ?int;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function toggleAtivo(int $id): bool;

    public function reordenar(array $ordens): bool;

    public function findById(int $id);
}
