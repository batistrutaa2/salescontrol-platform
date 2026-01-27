<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->unsignedBigInteger('backoffice_id')
                  ->nullable()
                  ->after('user_id');

            $table->foreign('backoffice_id')
                  ->references('id')
                  ->on('users')
                  ->onUpdate('no action')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::table('vendas', function (Blueprint $table) {
            $table->dropForeign(['backoffice_id']);
            $table->dropColumn('backoffice_id');
        });
    }
};
