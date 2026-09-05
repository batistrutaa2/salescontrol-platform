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
        Schema::table('credenciais_acesso', function (Blueprint $table) {
            $table->text('senha')->nullable()->change();
        });

        DB::table('credenciais_acesso')
            ->whereNotNull('senha')
            ->where('senha', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($credenciais) {
                foreach ($credenciais as $credencial) {
                    try {
                        Crypt::decryptString($credencial->senha);

                        continue;
                    } catch (DecryptException) {
                        // O valor legado ainda está em texto puro.
                    }

                    DB::table('credenciais_acesso')
                        ->where('id', $credencial->id)
                        ->update(['senha' => Crypt::encryptString($credencial->senha)]);
                }
            });

        // A trilha informa que a senha mudou, mas nunca deve conservar segredos.
        DB::table('credenciais_acesso_historico')
            ->whereRaw('LOWER(campo) = ?', ['senha'])
            ->update(['valor_anterior' => null, 'valor_novo' => null]);
    }

    public function down(): void
    {
        DB::table('credenciais_acesso')
            ->whereNotNull('senha')
            ->where('senha', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($credenciais) {
                foreach ($credenciais as $credencial) {
                    try {
                        $senha = Crypt::decryptString($credencial->senha);
                    } catch (DecryptException) {
                        $senha = $credencial->senha;
                    }

                    DB::table('credenciais_acesso')->where('id', $credencial->id)->update(['senha' => $senha]);
                }
            });

        Schema::table('credenciais_acesso', function (Blueprint $table) {
            $table->string('senha', 255)->nullable()->change();
        });
    }
};
