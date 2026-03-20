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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('operadora_id');
            $table->string('titulo');
            $table->text('resposta');
            $table->enum('status', ['Y', 'N'])->default('Y');
            $table->integer('ordem')->default(0);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')
                ->onUpdate('no action')->onDelete('no action');
            $table->foreign('operadora_id')->references('id')->on('operadoras')
                ->onUpdate('no action')->onDelete('no action');
            $table->index(['empresa_id', 'operadora_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
