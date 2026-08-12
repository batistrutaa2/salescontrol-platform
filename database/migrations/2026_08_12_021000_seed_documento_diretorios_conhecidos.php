<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documento_diretorios')) return;

        $pastas = [
            'Alice Saude', 'Allianz', 'AmePlan', 'Amil', 'BioVida', 'Blue', 'BlueMed',
            'Bradesco', 'CarePlus', 'CNU', 'CorpeSaude', 'CruzAzul', 'CuidarMe',
            'Declinados', 'DOCS', 'GNDI', 'HapVida', 'Leve Saúde', 'MedSenior', 'Omint',
            'Plena', 'Plena saude', 'PortoSeguro', 'PreventSenior', 'Qualicorp', 'Sami',
            'SamiSaude', 'Santa Helena', 'SaoCristovao', 'Seguros Unimed', 'Select',
            'SulAmerica', 'Super med', 'Trasmontano', 'Unimed Santos', 'VidaFit',
        ];

        foreach ($pastas as $pasta) {
            DB::table('documento_diretorios')->updateOrInsert(
                ['caminho' => 'EmAnalise/'.$pasta],
                ['nome' => $pasta, 'encontrado_em' => now(), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void {}
};
