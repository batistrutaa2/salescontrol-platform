<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venda_documentos', function (Blueprint $table) {
            $table->timestamp('verificado_em')->nullable()->after('erro');
            $table->timestamp('processamento_iniciado_em')->nullable()->after('verificado_em');
            $table->timestamp('ultima_tentativa_em')->nullable()->after('processamento_iniciado_em');
        });

        DB::table('venda_documentos')->where('status', 'AGUARDANDO')->update(['status' => 'RECEBIDO']);
        DB::table('venda_documentos')->where('status', 'VERIFICANDO')->update(['status' => 'RECEBIDO']);
        DB::table('venda_documentos')->where('status', 'ENVIANDO')->update(['status' => 'AGUARDANDO_ENVIO']);

        Schema::create('documento_diretorios', function (Blueprint $table) {
            $table->id();
            $table->string('caminho')->unique();
            $table->string('nome', 120)->index();
            $table->timestamp('encontrado_em');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_diretorios');
        Schema::table('venda_documentos', function (Blueprint $table) {
            $table->dropColumn(['verificado_em', 'processamento_iniciado_em', 'ultima_tentativa_em']);
        });
    }
};
