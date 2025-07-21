<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meta_configuracoes', function (Blueprint $table) {
            $table->decimal('valor_meta', 10, 2)->nullable()->after('data_fim');
            $table->integer('quantidade_meta')->nullable()->after('valor_meta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_configuracoes', function (Blueprint $table) {
            $table->dropColumn(['valor_meta', 'quantidade_meta']);
        });
    }
};
