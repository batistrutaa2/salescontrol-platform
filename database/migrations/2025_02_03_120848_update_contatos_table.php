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
        Schema::table('contatos', function (Blueprint $table) {
          $table->enum('is_ads', ['Y', 'N'])->default("N")->after('user_import_id');
          $table->string('tipo_criativo')->after('is_ads')->nullable();
          $table->string('vidas')->after('idades')->nullable();
          $table->enum('plano_ativo', ['Y', 'N'])->after('idades')->default("N");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contatos', function (Blueprint $table) {
            $table->dropColumn(['is_ads', 'tipo_criativo', 'vidas', 'plano_ativo']);
        });
    }
};
