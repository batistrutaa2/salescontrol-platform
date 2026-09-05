<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Services\TabulationCatalog;
use Illuminate\Database\Seeder;

class Tabulacoes extends Seeder
{
    public function run(TabulationCatalog $catalog): void
    {
        Empresa::query()->pluck('id')->each(
            fn (int $empresaId) => $catalog->provision($empresaId)
        );
    }
}
