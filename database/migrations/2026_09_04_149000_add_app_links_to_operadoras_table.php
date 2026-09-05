<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operadoras', function (Blueprint $table) {
            $table->string('app_ios_url', 500)->nullable()->after('logo_path');
            $table->string('app_android_url', 500)->nullable()->after('app_ios_url');
        });

        // Conversão única da experiência anterior. Em runtime, os links passam
        // a ser lidos exclusivamente da configuração da operadora do tenant.
        DB::table('operadoras')
            ->whereRaw('UPPER(nome) LIKE ?', ['%AMIL%'])
            ->update([
                'app_ios_url' => 'https://apps.apple.com/br/app/amil-clientes/id471890526',
                'app_android_url' => 'https://play.google.com/store/apps/details?id=br.com.amil.beneficiarios&hl=pt_BR',
            ]);
    }

    public function down(): void
    {
        Schema::table('operadoras', function (Blueprint $table) {
            $table->dropColumn(['app_ios_url', 'app_android_url']);
        });
    }
};
