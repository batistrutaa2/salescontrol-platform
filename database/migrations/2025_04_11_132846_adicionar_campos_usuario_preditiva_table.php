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
        Schema::table('preditiva', function (Blueprint $table) {
          $table->bigInteger('user_id')->unsigned()->nullable()->after('contato_id');
          $table->timestamp('data_atribuicao')->nullable()->after('user_id');
          $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preditiva', function (Blueprint $table) {
          $table->dropForeign(['user_id']);
          $table->dropColumn(['user_id', 'data_atribuicao']);
        });
    }
};
