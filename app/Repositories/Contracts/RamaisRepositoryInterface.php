<?php

namespace App\Repositories\Contracts;

use App\Models\Ramais;
use Illuminate\Support\Collection;

interface RamaisRepositoryInterface
{
    public function create(int $empresaId, int $userId, string $ramal): Ramais;

    public function getRamais(int $empresaId): Collection;

    public function getRamal(int $empresaId, int $userId): ?Ramais;
}
