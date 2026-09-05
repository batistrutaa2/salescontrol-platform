<?php

use App\Support\DocumentoFiscal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('cpf_cnpj_normalizado', 14)->nullable()->after('cpf_cnpj');
        });

        $usados = [];
        DB::table('empresas')->orderBy('id')->each(function ($empresa) use (&$usados): void {
            $documento = DocumentoFiscal::somenteDigitos($empresa->cpf_cnpj);
            if (! DocumentoFiscal::valido($documento) || isset($usados[$documento])) {
                return;
            }

            DB::table('empresas')->where('id', $empresa->id)->update([
                'cpf_cnpj' => $documento,
                'cpf_cnpj_normalizado' => $documento,
            ]);
            $usados[$documento] = true;
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->unique('cpf_cnpj_normalizado', 'empresas_cpf_cnpj_normalizado_unique');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropUnique('empresas_cpf_cnpj_normalizado_unique');
            $table->dropColumn('cpf_cnpj_normalizado');
        });
    }
};
