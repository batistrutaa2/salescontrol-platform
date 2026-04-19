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
        Schema::table('comercial_reunioes', function (Blueprint $table) {
            $table->unsignedBigInteger('contato_id')->nullable()->after('manager_id');

            $table->index('contato_id');
            $table->foreign('contato_id')->references('id')->on('contatos')
                ->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comercial_reunioes', function (Blueprint $table) {
            $table->dropForeign(['contato_id']);
            $table->dropIndex(['contato_id']);
            $table->dropColumn('contato_id');
        });
    }
};
