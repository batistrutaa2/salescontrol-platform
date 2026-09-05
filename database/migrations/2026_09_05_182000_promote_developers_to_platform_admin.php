<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_platform_admin')) {
            return;
        }

        DB::table('users')
            ->where('user_role_id', UserRole::DEVELOPER)
            ->update([
                'is_platform_admin' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Developer continua sendo o papel de acesso máximo da plataforma.
    }
};
