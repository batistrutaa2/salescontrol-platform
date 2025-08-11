<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ALTER TABLE usando DB::statement porque o enum precisa ser recriado
        DB::statement("ALTER TABLE vendas MODIFY coparticipacao ENUM('Y','N','PARCIAL','COMPLETA') NULL DEFAULT 'N'");
    }

    public function down(): void
    {
        // Volta para o enum original
        DB::statement("ALTER TABLE vendas MODIFY coparticipacao ENUM('Y','N') NULL DEFAULT 'N'");
    }
};
