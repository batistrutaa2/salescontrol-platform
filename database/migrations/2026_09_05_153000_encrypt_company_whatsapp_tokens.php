<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->text('whatsapp_token')->nullable()->change();
        });

        DB::table('empresas')
            ->whereNotNull('whatsapp_token')
            ->where('whatsapp_token', '!=', '')
            ->orderBy('id')
            ->each(function ($empresa): void {
                try {
                    Crypt::decryptString($empresa->whatsapp_token);

                    return;
                } catch (DecryptException) {
                    // O valor legado ainda está em texto puro.
                }

                DB::table('empresas')->where('id', $empresa->id)->update([
                    'whatsapp_token' => Crypt::encryptString($empresa->whatsapp_token),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('empresas')
            ->whereNotNull('whatsapp_token')
            ->where('whatsapp_token', '!=', '')
            ->orderBy('id')
            ->each(function ($empresa): void {
                try {
                    $token = Crypt::decryptString($empresa->whatsapp_token);
                } catch (DecryptException) {
                    $token = $empresa->whatsapp_token;
                }

                DB::table('empresas')->where('id', $empresa->id)->update(['whatsapp_token' => $token]);
            });

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('whatsapp_token', 500)->nullable()->change();
        });
    }
};
