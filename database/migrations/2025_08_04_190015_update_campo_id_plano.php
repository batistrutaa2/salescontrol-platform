<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->unsignedBigInteger('plano_id')->nullable()->after('telefone2');
            $table->foreign('plano_id')->references('id')->on('planos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropForeign(['plano_id']);
            $table->dropColumn('plano_id');
        });
    }

};
