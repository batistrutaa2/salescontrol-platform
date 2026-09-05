<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedTinyInteger('tv_percentual_atencao')
                ->default(50)
                ->after('escola_percentual_conclusao');
            $table->unsignedTinyInteger('tv_percentual_bom')
                ->default(75)
                ->after('tv_percentual_atencao');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['tv_percentual_atencao', 'tv_percentual_bom']);
        });
    }
};
