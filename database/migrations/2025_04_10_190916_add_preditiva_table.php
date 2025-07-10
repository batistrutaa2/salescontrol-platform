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
      Schema::create('preditiva', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('empresa_id')->default(0);
        $table->unsignedBigInteger('contato_id')->default(0);
        $table->enum('status', ['Y', 'N'])->default("Y");
        $table->timestamps();

        $table->primary('id');
        $table->foreign('empresa_id')->references('id')->on('empresas')->onUpdate('no action')->onDelete('no action');
        $table->foreign('contato_id')->references('id')->on('contatos')->onUpdate('no action')->onDelete('no action');
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::dropIfExists('preditiva');
    }
};
