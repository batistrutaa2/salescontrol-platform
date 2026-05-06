<?php

namespace App\Repositories\Contracts;

use Carbon\Carbon;

interface ResumoOperacionalRepositoryInterface
{
    public function vendasCadastradas(int $empresaId, Carbon $data): array;

    public function vendasImplantadas(int $empresaId, Carbon $data): array;

    public function boletosLiberadosHoje(int $empresaId, Carbon $data): int;

    public function topVendedoresDoDia(int $empresaId, Carbon $data, int $limit = 3): array;

    public function leadsTrabalhados(int $empresaId, Carbon $data): int;

    public function agendamentosAmanha(int $empresaId, Carbon $data): int;

    public function reunioesAmanhaPorGestor(int $empresaId, Carbon $data): array;
}
