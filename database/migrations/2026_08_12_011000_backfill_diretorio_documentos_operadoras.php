<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('operadoras', 'diretorio_documentos')) {
            return;
        }

        $diretorios = [
            'Alice Saude', 'Allianz', 'AmePlan', 'Amil', 'BioVida', 'Blue', 'BlueMed',
            'Bradesco', 'CarePlus', 'CNU', 'CorpeSaude', 'CruzAzul', 'CuidarMe', 'GNDI',
            'HapVida', 'Leve Saúde', 'MedSenior', 'Omint', 'Plena', 'Plena saude',
            'PortoSeguro', 'PreventSenior', 'Qualicorp', 'Sami', 'SamiSaude', 'Santa Helena',
            'SaoCristovao', 'Seguros Unimed', 'Select', 'SulAmerica', 'Super med',
            'Trasmontano', 'Unimed Santos', 'VidaFit',
        ];

        $normalizar = static fn (string $valor): string => strtoupper(
            preg_replace('/[^A-Z0-9]/', '', Str::ascii($valor)) ?? ''
        );

        $mapeamentos = [];
        foreach ($diretorios as $diretorio) {
            $mapeamentos[$normalizar($diretorio)] = $diretorio;
        }

        $mapeamentos = array_merge($mapeamentos, [
            'AMILSUPERMED' => 'Super med',
            'BRADESCOSAUDE' => 'Bradesco',
            'NOTREDAMEINTERMEDICA' => 'GNDI',
            'PORTOSEGUROSAUDE' => 'PortoSeguro',
            'SULAMERICASAUDE' => 'SulAmerica',
            'TRANSMONTANO' => 'Trasmontano',
        ]);

        DB::table('operadoras')->orderBy('id')->each(function ($operadora) use ($normalizar, $mapeamentos) {
            $diretorio = $mapeamentos[$normalizar((string) $operadora->nome)] ?? null;

            if ($diretorio !== null) {
                DB::table('operadoras')
                    ->where('id', $operadora->id)
                    ->update(['diretorio_documentos' => $diretorio]);
            }
        });
    }

    public function down(): void
    {
        // Mapeamentos podem ser alterados manualmente pelo painel; rollback não apaga esses dados.
    }
};
