<?php

namespace App\Repositories\Eloquent;

use App\Models\Empresa;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Services\TabulationCatalog;
use Illuminate\Support\Facades\DB;

class EmpresaRepository implements EmpresaRepositoryInterface
{
    protected $model;

    public function __construct(Empresa $model, private readonly TabulationCatalog $tabulations)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data): Empresa
    {
        return DB::transaction(function () use ($data): Empresa {
            $empresa = $this->model->create($data);
            $this->tabulations->provision((int) $empresa->id);

            return $empresa;
        });
    }

    public function getCompanies($field)
    {
        return $this->model::select($field)->get();
    }
}
