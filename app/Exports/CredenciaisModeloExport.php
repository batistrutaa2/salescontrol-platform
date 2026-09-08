<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CredenciaisModeloExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings
{
    public function headings(): array
    {
        return ['OPERADORA', 'TIPO', 'NOME', 'LOGIN', 'SENHA', 'OBSERVAÇÃO'];
    }

    public function array(): array
    {
        return [
            ['AMIL', 'Empresa', 'Empresa Exemplo Ltda.', '12.345.678/0001-90', 'Senha@123', 'Acesso ao portal empresarial'],
            ['SULAMÉRICA', 'Pessoa Física', 'Maria da Silva', '123.456.789-00', 'OutraSenha@456', 'Credencial do titular'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:F3');
            $sheet->getStyle('A1:F1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6845DF']],
            ]);
        }];
    }
}
