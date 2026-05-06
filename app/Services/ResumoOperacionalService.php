<?php

namespace App\Services;

use App\Repositories\Contracts\ResumoOperacionalRepositoryInterface;
use Carbon\Carbon;

class ResumoOperacionalService
{
    public function __construct(private ResumoOperacionalRepositoryInterface $repo) {}

    public function montarSnapshot(int $empresaId, Carbon $data): array
    {
        $vendasImplantadas = $this->repo->vendasImplantadas($empresaId, $data);

        return [
            'vendas_cadastradas' => $this->repo->vendasCadastradas($empresaId, $data),
            'vendas_implantadas' => $vendasImplantadas,
            'pipeline' => [
                'boleto_disponivel' => $this->repo->boletosLiberadosHoje($empresaId, $data),
                'implantadas'       => $vendasImplantadas['count'],
            ],
            'equipe' => [
                'leads' => $this->repo->leadsTrabalhados($empresaId, $data),
            ],
            'top_vendedores' => $this->repo->topVendedoresDoDia($empresaId, $data, 3),
            'amanha' => [
                'agendamentos'        => $this->repo->agendamentosAmanha($empresaId, $data),
                'reunioes_por_gestor' => $this->repo->reunioesAmanhaPorGestor($empresaId, $data),
            ],
        ];
    }
}
