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
        Schema::table('regras_comissionamento', function (Blueprint $table) {
            $table->decimal('percentual_vitalicio', 5, 2)->nullable()->after('vitalicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regras_comissionamento', function (Blueprint $table) {
            $table->dropColumn('percentual_vitalicio');
        });
    }
};
