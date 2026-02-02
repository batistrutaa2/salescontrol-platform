<?php

namespace App\Imports;

use App\Services\VendasSysImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VendasSysImport implements ToCollection, WithChunkReading, WithCustomCsvSettings, WithHeadingRow
{
    private VendasSysImportService $service;

    private int $currentLine = 1;

    public function __construct(int $empresaId, int $defaultUserId)
    {
        $this->service = new VendasSysImportService($empresaId, $defaultUserId);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->currentLine++;
            $rowArray = $row->toArray();

            // Pular linhas vazias
            if (empty(array_filter($rowArray))) {
                continue;
            }

            $this->service->processRow($rowArray, $this->currentLine);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'enclosure' => '"',
            'input_encoding' => 'UTF-8',
        ];
    }

    public function getService(): VendasSysImportService
    {
        return $this->service;
    }

    public function getImportados(): array
    {
        return $this->service->getImportados();
    }

    public function getDuplicados(): array
    {
        return $this->service->getDuplicados();
    }

    public function getErros(): array
    {
        return $this->service->getErros();
    }

    public function getWarnings(): array
    {
        return $this->service->getWarnings();
    }

    public function getResumo(): array
    {
        return $this->service->getResumo();
    }
}
