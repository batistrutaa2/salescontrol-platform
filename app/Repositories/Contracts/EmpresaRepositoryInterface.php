<?php

namespace App\Repositories\Contracts;

use App\Models\Empresa;

interface EmpresaRepositoryInterface
{
    public function getCompanies(array $data);

    public function create(array $data): Empresa;

    public function all();

    public function find($id);
}
