<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->time('reuniao_horario_inicio')->default('08:00:00')->after('whatsapp_token');
            $table->time('reuniao_horario_fim')->default('18:00:00')->after('reuniao_horario_inicio');
            $table->unsignedSmallInteger('reuniao_duracao_minutos')->default(60)->after('reuniao_horario_fim');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'reuniao_horario_inicio',
                'reuniao_horario_fim',
                'reuniao_duracao_minutos',
            ]);
        });
    }
};
