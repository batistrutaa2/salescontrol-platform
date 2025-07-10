<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class EmpresasSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');
    DB::table('empresas')->insert([
      ['id' => 1, 'nome_fantasia' => 'BRSOLUTIONS', 'cpf_cnpj' => '47633852836', 'telefone' => '11945567166', 'email' => 'brsolutions@outlook.com.br', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
      ['id' => 2, 'nome_fantasia' => 'LKBROKERS', 'cpf_cnpj' => '12345687454871', 'telefone' => '1199007214', 'email' => 'LKBROKERS@outlook.com.br', 'created_at' => $currentDateTime, 'updated_at' => $currentDateTime],
    ]);
  }
}
