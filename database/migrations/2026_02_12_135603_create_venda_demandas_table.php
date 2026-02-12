<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('venda_demandas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('venda_id')->constrained('vendas')->onDelete('cascade');
      $table->foreignId('empresa_id')->constrained('empresas');
      $table->foreignId('created_by')->constrained('users');
      $table->foreignId('concluida_por')->nullable()->constrained('users');
      $table->string('tipo');
      $table->string('titulo');
      $table->text('descricao')->nullable();
      $table->enum('status', ['PENDENTE', 'CONCLUIDA'])->default('PENDENTE');
      $table->timestamp('concluida_em')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('venda_demandas');
  }
};
