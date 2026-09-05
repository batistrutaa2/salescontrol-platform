<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_service_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('service', 64);
            $table->string('endpoint', 2048)->nullable();
            $table->longText('credentials')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'service']);
            $table->index(['empresa_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_service_credentials');
    }
};
