<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Tabulacoes extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');
    DB::table('tabulacoes')->insert([
      ['id' => 1, 'empresa_id' => 1, 'descricao' => 'PROSPECÇÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'A', 'status' => 'Y', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 2, 'empresa_id' => 1, 'descricao' => 'REUNIÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'B', 'status' => 'Y', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 3, 'empresa_id' => 1, 'descricao' => 'NEGOCIAÇÃO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'C', 'status' => 'Y', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 4, 'empresa_id' => 1, 'descricao' => 'DOCUMENTO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'D', 'status' => 'Y', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 5, 'empresa_id' => 1, 'descricao' => 'NEGOCIO FECHADO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'E', 'status' => 'Y', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 6, 'empresa_id' => 1, 'descricao' => 'NEGOCIO NAO FECHADO', 'tipo_tabulacao' => 'C', 'efetivo' => 'Y', 'ordem_kanban' => 'F', 'status' => 'Y', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
    ]);
  }
}
