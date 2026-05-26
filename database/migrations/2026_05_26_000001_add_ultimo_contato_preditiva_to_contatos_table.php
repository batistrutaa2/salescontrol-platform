<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('contatos', function (Blueprint $table) {
      $table->timestamp('ultimo_contato_preditiva')->nullable()->after('valor_plano_atual');
    });

    // Backfill: recupera o que ainda restou em log_preditiva (descarte/conversao do vendedor).
    // Preenche APENAS a coluna nova; nao altera nenhuma outra data de contato.
    DB::statement("
      UPDATE contatos c
      SET c.ultimo_contato_preditiva = (
        SELECT MAX(lp.created_at)
        FROM log_preditiva lp
        WHERE lp.contato_id = c.id
          AND lp.empresa_id = c.empresa_id
          AND lp.acao IN ('DESCARTE', 'CONVERSAO')
      )
    ");
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('contatos', function (Blueprint $table) {
      $table->dropColumn('ultimo_contato_preditiva');
    });
  }
};
