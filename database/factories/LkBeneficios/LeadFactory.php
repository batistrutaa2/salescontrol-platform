<?php

namespace Database\Factories\LkBeneficios;

use App\Modules\LkBeneficios\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'empresa_id' => 1,
            'user_id' => 1,
            'produto_interesse_id' => 1,
            'cliente_tipo' => 'PF',
            'cpf_cnpj' => fake()->numerify('###########'),
            'nome' => fake()->name(),
            'email' => fake()->safeEmail(),
            'telefone' => fake()->numerify('###########'),
            'status' => 'NOVO_CLIENTE',
            'origem' => 'MANUAL',
            'ordem_kanban' => 0,
            'data_ultima_movimentacao' => now(),
        ];
    }
}
