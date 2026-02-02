<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vendas MODIFY COLUMN layout_venda ENUM('ANTIGO', 'NOVO', 'IMPORTACAO_SYS') DEFAULT 'ANTIGO'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE vendas MODIFY COLUMN layout_venda ENUM('ANTIGO', 'NOVO') DEFAULT 'ANTIGO'");
    }
};
