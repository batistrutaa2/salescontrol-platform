<?php

namespace App\Modules\LkBeneficios\Repositories\Contracts;

use App\Modules\LkBeneficios\Models\Produto;
use Illuminate\Database\Eloquent\Builder;

interface ProdutoRepositoryInterface
{
    public function queryForDataTable(int $empresaId, array $filtros = []): Builder;

    public function findByEmpresa(int $id, int $empresaId): ?Produto;

    public function create(array $data): Produto;

    public function update(int $id, int $empresaId, array $data): Produto;

    public function delete(int $id, int $empresaId): bool;

    public function setAtivo(int $id, int $empresaId, bool $ativo): Produto;

    public function temContratosVinculados(int $id, int $empresaId): bool;
}
