<?php

namespace Database\Factories\LkBeneficios;

use App\Modules\LkBeneficios\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        return [
            'empresa_id' => 1,
            'nome' => fake()->randomElement(['Seguro de Vida Individual', 'Plano Odontológico PF', 'Previdência VGBL']),
            'tipo' => fake()->randomElement(['VIDA', 'ODONTO', 'PREVIDENCIA', 'PATRIMONIAL']),
            'subtipo' => null,
            'operadora_id' => null,
            'ativo' => true,
        ];
    }
}
