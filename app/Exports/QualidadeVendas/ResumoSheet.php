<?php

namespace App\Exports\QualidadeVendas;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ResumoSheet implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithTitle
{
    public function __construct(private readonly array $resumo) {}

    public function title(): string
    {
        return 'Auditoria por vendedor';
    }

    public function headings(): array
    {
        return [
            'Posição', 'Vendedor', 'Total propostas', 'Vendido bruto', 'Válido para ranking',
            'Implantadas', 'Valor implantado', 'Em processo', 'Valor em processo',
            'Estornos', 'Valor estornado', 'Declínios', 'Valor declinado',
            '% implantação', '% perda',
        ];
    }

    public function array(): array
    {
        $linhas = [];

        foreach ($this->resumo['vendedores'] as $vendedor) {
            $linhas[] = $this->linha($vendedor['vendedor'], $vendedor, $vendedor['posicao_geral']);
        }

        return $linhas;
    }

    public function columnFormats(): array
    {
        return [
            'D' => '"R$" #,##0.00',
            'E' => '"R$" #,##0.00',
            'G' => '"R$" #,##0.00',
            'I' => '"R$" #,##0.00',
            'K' => '"R$" #,##0.00',
            'M' => '"R$" #,##0.00',
            'N' => NumberFormat::FORMAT_PERCENTAGE_00,
            'O' => NumberFormat::FORMAT_PERCENTAGE_00,
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->freezePane('A2');
            $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
            $sheet->getStyle('A1:O1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6D5DFC']],
            ]);
        }];
    }

    private function linha(string $nome, array $dados, ?int $posicao = null): array
    {
        return [
            $posicao, $nome, $dados['total_propostas'], $dados['valor_bruto'], $dados['valor_valido'],
            $dados['implantadas'], $dados['valor_implantado'], $dados['em_processo'], $dados['valor_em_processo'],
            $dados['estornos'], $dados['valor_estornado'], $dados['declinios'], $dados['valor_declinado'],
            $dados['percentual_implantacao'] / 100, $dados['percentual_perda'] / 100,
        ];
    }
}
