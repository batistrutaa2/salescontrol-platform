<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('pos_venda_demanda_templates', function (Blueprint $table) {
      $table->id();
      $table->foreignId('empresa_id')->constrained('empresas');
      $table->string('tipo');
      $table->string('titulo');
      $table->text('descricao')->nullable();
      $table->boolean('gerar_automatico')->default(true);
      $table->boolean('ativo')->default(true);
      $table->integer('ordem')->default(0);
      $table->timestamps();

      $table->index(['empresa_id', 'ativo']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('pos_venda_demanda_templates');
  }
};
