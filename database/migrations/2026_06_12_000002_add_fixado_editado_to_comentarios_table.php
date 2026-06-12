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
        Schema::table('comentarios', function (Blueprint $table) {
            $table->enum('fixado', ['Y', 'N'])->default('N')->after('supervisao');
            $table->timestamp('fixado_em')->nullable()->after('fixado');
            $table->timestamp('editado_em')->nullable()->after('fixado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropColumn(['fixado', 'fixado_em', 'editado_em']);
        });
    }
};
