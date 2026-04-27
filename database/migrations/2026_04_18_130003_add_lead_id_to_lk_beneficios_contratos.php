<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lk_beneficios_contratos', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable()->after('venda_origem_id');
            $table->index('lead_id');
            $table->foreign('lead_id')->references('id')->on('lk_beneficios_leads')
                ->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('lk_beneficios_contratos', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropIndex(['lead_id']);
            $table->dropColumn('lead_id');
        });
    }
};
