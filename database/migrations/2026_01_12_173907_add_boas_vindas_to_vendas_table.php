<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->timestamp('boas_vindas_enviado_em')->nullable()->after('data_implantacao');
            $table->unsignedBigInteger('boas_vindas_enviado_por')->nullable()->after('boas_vindas_enviado_em');
            $table->foreign('boas_vindas_enviado_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropForeign(['boas_vindas_enviado_por']);
            $table->dropColumn(['boas_vindas_enviado_em', 'boas_vindas_enviado_por']);
        });
    }
};
