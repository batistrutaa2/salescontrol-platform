<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->date('data_nascimento')->nullable()->index();
            $table->date('data_nascimento_notified_at')->nullable()->index();
        });
    }
    
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birthdate','data_nascimento_notified_at']);
        });
    }
};