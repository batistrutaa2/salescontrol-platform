<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operadoras', function (Blueprint $table) {
            $table->string('diretorio_documentos', 120)->nullable()->after('nome');
        });

        $mapeamentos = [
            'ALICE' => 'Alice Saude',
            'ALICE SAUDE' => 'Alice Saude',
            'ALLIANZ' => 'Allianz',
            'AMEPLAN' => 'AmePlan',
            'AMIL' => 'Amil',
            'BIOVIDA' => 'BioVida',
            'BLUE' => 'Blue',
            'BLUEMED' => 'BlueMed',
            'BRADESCO' => 'Bradesco',
            'CARE PLUS' => 'CarePlus',
            'CAREPLUS' => 'CarePlus',
            'CNU' => 'CNU',
            'CORPE SAUDE' => 'CorpeSaude',
            'CORPESAUDE' => 'CorpeSaude',
            'CRUZ AZUL' => 'CruzAzul',
            'CRUZAZUL' => 'CruzAzul',
            'CUIDAR ME' => 'CuidarMe',
            'CUIDARME' => 'CuidarMe',
            'GNDI' => 'GNDI',
            'HAPVIDA' => 'HapVida',
            'LEVE SAUDE' => 'Leve Saúde',
            'MEDSENIOR' => 'MedSenior',
            'OMINT' => 'Omint',
            'PLENA' => 'Plena',
            'PLENA SAUDE' => 'Plena saude',
            'PORTO SEGURO' => 'PortoSeguro',
            'PORTOSEGURO' => 'PortoSeguro',
            'PREVENT SENIOR' => 'PreventSenior',
            'PREVENTSENIOR' => 'PreventSenior',
            'QUALICORP' => 'Qualicorp',
            'SAMI' => 'Sami',
            'SAMI SAUDE' => 'SamiSaude',
            'SANTA HELENA' => 'Santa Helena',
            'SAO CRISTOVAO' => 'SaoCristovao',
            'SEGUROS UNIMED' => 'Seguros Unimed',
            'SELECT' => 'Select',
            'SULAMERICA' => 'SulAmerica',
            'SUL AMERICA' => 'SulAmerica',
            'SUPERMED' => 'Super med',
            'SUPER MED' => 'Super med',
            'AMIL - SUPERMED' => 'Super med',
            'TRASMONTANO' => 'Trasmontano',
            'UNIMED SANTOS' => 'Unimed Santos',
            'VIDAFIT' => 'VidaFit',
            'VIDA FIT' => 'VidaFit',
        ];

        foreach ($mapeamentos as $operadora => $diretorio) {
            DB::table('operadoras')
                ->whereRaw('UPPER(TRIM(nome)) = ?', [$operadora])
                ->update(['diretorio_documentos' => $diretorio]);
        }

        $diretoriosExistentes = [
            'Alice Saude', 'Allianz', 'AmePlan', 'Amil', 'BioVida', 'Blue', 'BlueMed',
            'Bradesco', 'CarePlus', 'CNU', 'CorpeSaude', 'CruzAzul', 'CuidarMe', 'GNDI',
            'HapVida', 'Leve Saúde', 'MedSenior', 'Omint', 'Plena', 'Plena saude',
            'PortoSeguro', 'PreventSenior', 'Qualicorp', 'Sami', 'SamiSaude', 'Santa Helena',
            'SaoCristovao', 'Seguros Unimed', 'Select', 'SulAmerica', 'Super med',
            'Trasmontano', 'Unimed Santos', 'VidaFit',
        ];
        $normalizar = static fn (string $valor): string => strtoupper(preg_replace('/[^A-Z0-9]/', '', Str::ascii($valor)) ?? '');
        $porNomeNormalizado = [];
        foreach ($diretoriosExistentes as $diretorio) {
            $porNomeNormalizado[$normalizar($diretorio)] = $diretorio;
        }
        $porNomeNormalizado['AMILSUPERMED'] = 'Super med';
        $porNomeNormalizado['BRADESCOSAUDE'] = 'Bradesco';
        $porNomeNormalizado['NOTREDAMEINTERMEDICA'] = 'GNDI';
        $porNomeNormalizado['TRANSMONTANO'] = 'Trasmontano';

        DB::table('operadoras')->whereNull('diretorio_documentos')->orderBy('id')->each(function ($operadora) use ($normalizar, $porNomeNormalizado) {
            $diretorio = $porNomeNormalizado[$normalizar((string) $operadora->nome)] ?? null;
            if ($diretorio) {
                DB::table('operadoras')->where('id', $operadora->id)->update(['diretorio_documentos' => $diretorio]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('operadoras', function (Blueprint $table) {
            $table->dropColumn('diretorio_documentos');
        });
    }
};
