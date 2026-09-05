<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->char('token_hash', 64)->unique();
            $table->json('abilities');
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_integration_credentials');
    }
};
