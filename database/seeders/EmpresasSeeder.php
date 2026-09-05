<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresasSeeder extends Seeder
{
    public function run(): void
    {
        Empresa::query()->updateOrCreate(
            ['email' => config('tenancy.bootstrap.company_email')],
            [
                'nome_fantasia' => config('tenancy.bootstrap.company_name'),
                'cpf_cnpj' => config('tenancy.bootstrap.company_document'),
                'telefone' => config('tenancy.bootstrap.company_phone'),
            ]
        );
    }
}
