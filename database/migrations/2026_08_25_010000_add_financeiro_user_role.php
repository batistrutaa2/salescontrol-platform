<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_roles')->updateOrInsert(
            ['id' => 8],
            [
                'tipo_usuario' => 'FINANCEIRO',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('user_roles')->where('id', 8)->delete();
    }
};
