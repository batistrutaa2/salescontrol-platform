<?php

namespace App\Exports;

use App\Exports\QualidadeVendas\ResumoSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RelatorioQualidadeVendasExport implements WithMultipleSheets
{
    public function __construct(private readonly array $resumo) {}

    public function sheets(): array
    {
        return [
            new ResumoSheet($this->resumo),
        ];
    }
}
