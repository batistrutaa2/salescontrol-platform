<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Modules\LkBeneficios\Models\Produto;
use Illuminate\Database\Seeder;

class LkBeneficiosProdutosSeeder extends Seeder
{
    public function run(): void
    {
        $produtosBase = [
            ['tipo' => 'VIDA', 'nome' => 'Seguro de Vida Individual', 'subtipo' => 'Individual'],
            ['tipo' => 'VIDA', 'nome' => 'Seguro de Vida em Grupo', 'subtipo' => 'Grupo'],
            ['tipo' => 'ODONTO', 'nome' => 'Plano Odontológico PF', 'subtipo' => 'Pessoa Física'],
            ['tipo' => 'ODONTO', 'nome' => 'Plano Odontológico PME', 'subtipo' => 'Pequena/Média Empresa'],
            ['tipo' => 'PREVIDENCIA', 'nome' => 'Previdência VGBL', 'subtipo' => 'VGBL'],
            ['tipo' => 'PREVIDENCIA', 'nome' => 'Previdência PGBL', 'subtipo' => 'PGBL'],
            ['tipo' => 'PATRIMONIAL', 'nome' => 'Seguro Auto', 'subtipo' => 'Auto'],
            ['tipo' => 'PATRIMONIAL', 'nome' => 'Seguro Residencial', 'subtipo' => 'Residencial'],
            ['tipo' => 'PATRIMONIAL', 'nome' => 'Seguro Empresarial', 'subtipo' => 'Empresarial'],
        ];

        $empresas = Empresa::query()->pluck('id');

        foreach ($empresas as $empresaId) {
            foreach ($produtosBase as $p) {
                Produto::updateOrCreate(
                    [
                        'empresa_id' => $empresaId,
                        'tipo' => $p['tipo'],
                        'nome' => $p['nome'],
                    ],
                    [
                        'subtipo' => $p['subtipo'],
                        'ativo' => true,
                    ]
                );
            }
        }
    }
}
