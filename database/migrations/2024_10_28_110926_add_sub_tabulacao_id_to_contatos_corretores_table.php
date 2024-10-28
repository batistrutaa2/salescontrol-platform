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
        Schema::table('contatos_corretores', function (Blueprint $table) {
          $table->unsignedBigInteger('sub_tabulacao_id')->nullable()->after('tabulacao_id');

          $table->foreign('sub_tabulacao_id')->references('id')->on('tabulacoes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contatos_corretores', function (Blueprint $table) {
          $table->dropForeign(['sub_tabulacao_id']);
          $table->dropColumn('sub_tabulacao_id');
        });
    }
};
