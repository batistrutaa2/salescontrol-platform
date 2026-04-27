<?php

namespace App\Modules\LkBeneficios\Repositories\Contracts;

use Illuminate\Database\Query\Builder;

interface BaseSaudeRepositoryInterface
{
    public function queryClientesParaAquisicao(int $empresaId, array $filtros = []): Builder;

    public function buscarDadosConsolidadosPorCpfCnpj(string $cpfCnpj, int $empresaId): ?array;
}
