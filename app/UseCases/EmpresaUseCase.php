<?php

namespace App\UseCases;

use App\Models\Empresa;
use App\Repositories\Contracts\EmpresaRepositoryInterface;

class EmpresaUseCase
{
    protected $empresaRepository;

    public function __construct(EmpresaRepositoryInterface $empresaRepository)
    {
        $this->empresaRepository = $empresaRepository;
    }

    public function createCompany(array $data): Empresa
    {
        return $this->empresaRepository->create($data);
    }
}
