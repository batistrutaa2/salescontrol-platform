<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('vendas', function (Blueprint $table) {
      $table->timestamp('pos_venda_concluida_em')->nullable()->after('boas_vindas_enviado_por');
      $table->unsignedBigInteger('pos_venda_concluida_por')->nullable()->after('pos_venda_concluida_em');
    });
  }

  public function down(): void
  {
    Schema::table('vendas', function (Blueprint $table) {
      $table->dropColumn(['pos_venda_concluida_em', 'pos_venda_concluida_por']);
    });
  }
};
