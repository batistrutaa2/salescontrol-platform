<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nome_fantasia' => fake()->company(),
            'cpf_cnpj' => fake()->numerify('##############'),
            'telefone' => fake()->numerify('(##) #####-####'),
        ];
    }
}
