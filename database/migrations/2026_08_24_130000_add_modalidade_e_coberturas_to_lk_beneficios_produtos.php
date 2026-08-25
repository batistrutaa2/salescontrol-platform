<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lk_beneficios_produtos', function (Blueprint $table) {
            $table->string('modalidade', 40)->nullable()->after('subtipo');
            $table->json('coberturas')->nullable()->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('lk_beneficios_produtos', function (Blueprint $table) {
            $table->dropColumn(['modalidade', 'coberturas']);
        });
    }
};
